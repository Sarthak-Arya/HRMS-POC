<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceEntrySource;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\MonthlyAttendance;
use App\Services\Employee\EmployeeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function search(int $companyId, int $month, int $year, array $filters = [], int $limit = 50): Collection
    {
        if (! Schema::hasTable('employee_attendance_summaries')) {
            return collect();
        }

        $query = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['employee.department', 'employee.designation', 'leaveBreakdown.leaveType']);

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department'])) {
            $department = (string) $filters['department'];
            $query->whereHas('employee.department', function ($q) use ($department) {
                $q->where('department_name', 'like', "%{$department}%");
            });
        }

        return $query->limit($limit)->get()->map(fn (MonthlyAttendance $record) => $this->toSummary($record));
    }

    public function findForEmployee(
        int $companyId,
        int $month,
        int $year,
        ?int $employeeId = null,
        ?string $employeeCode = null,
    ): ?MonthlyAttendance {
        if (! Schema::hasTable('employee_attendance_summaries')) {
            return null;
        }

        $employee = app(EmployeeService::class)->findForCompany($companyId, $employeeId, $employeeCode);
        if (!$employee) {
            return null;
        }

        return MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['employee.department', 'employee.designation', 'leaveBreakdown.leaveType'])
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function upsertFromAgent(int $companyId, array $data): array
    {
        return app(AttendanceCommandService::class)->upsertMonthlyRecord($companyId, $data);
    }

    /**
     * @return list<string>
     */
    public function buildImportTemplateColumns(int $companyId): array
    {
        $columns = ['employee_code', 'month', 'year'];

        LeaveType::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->pluck('code')
            ->each(function (string $code) use (&$columns) {
                $columns[] = strtolower($code);
            });

        return array_merge($columns, ['esi_leave', 'holiday', 'tot_dys', 'working_days']);
    }

    /**
     * @return array{records: list<array<string, mixed>>, skipped: int, errors: array<int, string>}
     */
    public function parseExcelRecords(int $companyId, string $filePath, ?int $defaultMonth = null, ?int $defaultYear = null): array
    {
        $rows = Excel::toArray(null, $filePath)[0] ?? [];
        if ($rows === []) {
            return [
                'records' => [],
                'skipped' => 0,
                'errors' => [0 => 'Excel file is empty.'],
            ];
        }

        $header = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), $rows[0]);
        unset($rows[0]);

        $records = [];
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $data = $this->mapImportRow($header, $row);
            if (empty($data['month']) && $defaultMonth !== null) {
                $data['month'] = $defaultMonth;
            }
            if (empty($data['year']) && $defaultYear !== null) {
                $data['year'] = $defaultYear;
            }

            if ($this->importRowIsEmpty($data)) {
                $skipped++;
                continue;
            }

            if (empty($data['month']) || empty($data['year'])) {
                $errors[$index + 2] = 'Month and year are required for each row (or provide defaults).';
                continue;
            }

            $records[] = $this->normalizeImportRecord($companyId, $data);
        }

        return compact('records', 'skipped', 'errors');
    }

    /**
     * @return array{created: int, updated: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function importFromExcel(int $companyId, string $filePath, ?int $defaultMonth = null, ?int $defaultYear = null): array
    {
        if (! Schema::hasTable('employee_attendance_summaries')) {
            return [
                'created' => 0,
                'updated' => 0,
                'failed' => 1,
                'skipped' => 0,
                'errors' => [0 => 'Monthly attendance summaries are not configured.'],
            ];
        }

        $parsed = $this->parseExcelRecords($companyId, $filePath, $defaultMonth, $defaultYear);
        if ($parsed['records'] === [] && $parsed['errors'] !== []) {
            return [
                'created' => 0,
                'updated' => 0,
                'failed' => count($parsed['errors']),
                'skipped' => $parsed['skipped'],
                'errors' => $parsed['errors'],
            ];
        }

        $result = $this->bulkUpsertFromAgent($companyId, $parsed['records']);

        return [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'failed' => $result['failed'] + count($parsed['errors']),
            'skipped' => $parsed['skipped'],
            'errors' => $parsed['errors'] + $result['errors'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>}
     */
    public function bulkUpsertFromAgent(int $companyId, array $records): array
    {
        return app(AttendanceCommandService::class)->importMonthly($companyId, $records);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummary(?MonthlyAttendance $record): array
    {
        if (!$record) {
            return [];
        }

        $employee = $record->employee;

        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'employee_code' => $employee?->employee_code,
            'employee_name' => $employee?->employee_name,
            'department' => $employee?->department?->department_name,
            'designation' => $employee?->designation?->designation_name,
            'month' => $record->month,
            'year' => $record->year,
            'cl' => (float) $record->casual_leave,
            'el' => (float) $record->earned_leave,
            'sl' => (float) $record->sick_leave,
            'esi_leave' => (float) $record->esi_la,
            'holiday' => (float) $record->holiday,
            'tot_dys' => (float) $record->total_days,
            'worked_days' => (float) $record->worked_days,
            'overtime_days' => (float) $record->overtime_days,
            'overtime_hours' => (float) $record->overtime_hours,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  keyed by employee_id
     */
    public function saveMonthlyBatch(int $companyId, int $month, int $year, array $rows): int
    {
        return app(AttendanceCommandService::class)->saveMonthly($companyId, $month, $year, $rows);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, float>  $leaveDays
     * @param  array<string, mixed>  $calculated
     */
    public function persistMonthlySummary(
        int $companyId,
        Employee $employee,
        int $month,
        int $year,
        array $validated,
        array $calculated,
        array $leaveDays,
        ?\App\Models\AttendancePolicy $policy,
        ?MonthlyAttendance $existing = null,
    ): MonthlyAttendance {
        $record = MonthlyAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'policy_id' => $policy?->id ?? $existing?->policy_id,
                'entry_source' => AttendanceEntrySource::MANUAL->value,
                'calendar_days' => $calculated['calendar_days'],
                'holiday_days' => $calculated['holiday_days'],
                'total_days' => $calculated['total_days'],
                'working_days' => $calculated['working_days'],
                'present_days' => $calculated['present_days'],
                'half_days' => $calculated['half_days'] ?? 0,
                'paid_leave_days' => $calculated['paid_leave_days'],
                'lop_days' => $calculated['lop_days'] ?? 0,
                'weekly_off_days' => $calculated['weekly_off_days'] ?? 0,
                'esi_la' => $calculated['esi_la'] ?? (float) ($validated['esi_leave'] ?? $existing?->esi_la ?? 0),
                'worked_days' => $calculated['worked_days'],
                'overtime_days' => (float) ($validated['overtime_days'] ?? $existing?->overtime_days ?? 0),
                'overtime_hours' => (float) ($validated['overtime_hours'] ?? $existing?->overtime_hours ?? 0),
                'shift_code' => $validated['shift_code'] ?? $existing?->shift_code,
            ],
        );

        $record->syncLeaveBreakdown($leaveDays);
        $record->load(['employee.department', 'employee.designation', 'leaveBreakdown.leaveType']);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateAgentDataPublic(int $companyId, array $data): array
    {
        return $this->validateAgentData($companyId, $data, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeAgentRowPublic(array $data): array
    {
        return $this->normalizeAgentRow($data);
    }

    public function isSummaryLocked(MonthlyAttendance $summary): bool
    {
        return $summary->locked_at !== null;
    }

    public function lockSummary(MonthlyAttendance $summary): void
    {
        $summary->update(['locked_at' => now()]);
    }

    public function unlockSummary(MonthlyAttendance $summary): void
    {
        $summary->update(['locked_at' => null]);
    }

    /**
     * @return array{consistent: bool, message: string|null}
     */
    public function reconcileSummary(MonthlyAttendance $summary): array
    {
        if ($summary->entry_source?->value === 'daily_aggregated') {
            return ['consistent' => true, 'message' => null];
        }

        $employee = $summary->employee;
        if (! $employee) {
            return ['consistent' => false, 'message' => 'Employee not found for summary.'];
        }

        $dailyExists = \App\Models\EmployeeAttendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [
                \Carbon\Carbon::createFromDate($summary->year, $summary->month, 1)->startOfMonth()->toDateString(),
                \Carbon\Carbon::createFromDate($summary->year, $summary->month, 1)->endOfMonth()->toDateString(),
            ])
            ->exists();

        if ($dailyExists && $summary->entry_source?->value === 'manual') {
            return [
                'consistent' => false,
                'message' => 'Daily attendance records exist but summary was manually entered.',
            ];
        }

        return ['consistent' => true, 'message' => null];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validateAgentData(int $companyId, array $data, bool $requireEmployee): array
    {
        $normalized = $this->normalizeAgentRow($data);

        $rules = [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'cl' => 'nullable|numeric|min:0',
            'el' => 'nullable|numeric|min:0',
            'sl' => 'nullable|numeric|min:0',
            'lwp' => 'nullable|numeric|min:0',
            'leaves' => 'nullable|array',
            'leaves.*' => 'nullable|numeric|min:0',
            'esi_leave' => 'nullable|numeric|min:0',
            'holiday' => 'nullable|numeric|min:0',
            'tot_dys' => 'nullable|numeric|min:0',
            'overtime_days' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'shift_code' => 'nullable|string|max:20',
            'deductions' => 'nullable|array',
            'deductions.*' => 'numeric|min:0',
        ];

        if ($requireEmployee) {
            $rules['employee_id'] = 'nullable|integer';
            $rules['employee_code'] = 'nullable|string';
        }

        $validator = Validator::make($normalized, $rules);

        $validator->after(function ($validator) use ($normalized, $requireEmployee) {
            if (!$requireEmployee) {
                return;
            }

            [$employeeId, $employeeCode] = app(EmployeeService::class)->resolveEmployeeIdentifier($normalized);
            if ($employeeId === null && $employeeCode === null) {
                $validator->errors()->add('employee', 'employee_id or employee_code is required.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeAgentRow(array $data): array
    {
        $normalized = $data;

        foreach ([
            'casual_leave' => 'cl',
            'earned_leave' => 'el',
            'sick_leave' => 'sl',
            'esi_la' => 'esi_leave',
            'total_days' => 'tot_dys',
        ] as $long => $short) {
            if (!isset($normalized[$short]) && isset($normalized[$long])) {
                $normalized[$short] = $normalized[$long];
            }
        }

        if (! isset($normalized['deductions']) || ! is_array($normalized['deductions'])) {
            $deductions = [];
            for ($i = 1; $i <= 50; $i++) {
                $key = 'ded_' . $i;
                if (! array_key_exists($key, $normalized)) {
                    break;
                }
                $deductions[] = (float) ($normalized[$key] ?? 0);
                unset($normalized[$key]);
            }
            if ($deductions !== []) {
                $normalized['deductions'] = $deductions;
            }
        }

        if (isset($normalized['deductions']) && is_array($normalized['deductions'])) {
            $normalized['deductions'] = array_values($normalized['deductions']);
        }

        [$employeeId, $employeeCode] = app(EmployeeService::class)->resolveEmployeeIdentifier($normalized);
        unset($normalized['employee_id'], $normalized['employee_code']);
        if ($employeeId !== null) {
            $normalized['employee_id'] = $employeeId;
        }
        if ($employeeCode !== null) {
            $normalized['employee_code'] = $employeeCode;
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $header
     * @param array<int, mixed> $row
     * @return array<string, mixed>
     */
    private function mapImportRow(array $header, array $row): array
    {
        $data = [];
        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }
            $data[$column] = $row[$index] ?? null;
        }

        foreach (['month', 'year'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }

        for ($i = 1; $i <= 50; $i++) {
            $key = 'ded_' . $i;
            if (!array_key_exists($key, $data)) {
                break;
            }
            $data['deductions'] ??= [];
            $data['deductions'][] = (float) ($data[$key] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function importRowIsEmpty(array $data): bool
    {
        [$employeeId, $employeeCode] = app(EmployeeService::class)->resolveEmployeeIdentifier($data);

        return $employeeId === null && $employeeCode === null;
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[\s\-]+/', '_', $header) ?? $header;

        return match ($header) {
            'casual_leave' => 'cl',
            'earned_leave' => 'el',
            'sick_leave' => 'sl',
            'leave_without_pay' => 'lwp',
            'esi_la', 'esi_leave' => 'esi_leave',
            'total_days' => 'tot_dys',
            'empno', 'emp_no', 'employee_no', 'emp_code', 'company_code' => 'employee_code',
            default => $header,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeImportRecord(int $companyId, array $data): array
    {
        $leaves = is_array($data['leaves'] ?? null) ? $data['leaves'] : [];

        LeaveType::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->each(function (LeaveType $leaveType) use (&$data, &$leaves) {
                $key = strtolower($leaveType->code);
                if (! array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
                    return;
                }

                $leaves[$leaveType->code] = (float) $data[$key];
                unset($data[$key]);
            });

        foreach (['cl' => 'CL', 'el' => 'EL', 'sl' => 'SL'] as $short => $code) {
            if (array_key_exists($short, $data) && $data[$short] !== '' && $data[$short] !== null) {
                $leaves[$code] = (float) $data[$short];
                unset($data[$short]);
            }
        }

        if ($leaves !== []) {
            $data['leaves'] = $leaves;
        }

        return $this->normalizeAgentRow($data);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    /**
     * @return array<string, float>
     */
    private function extractLeaveDays(array $validated, ?MonthlyAttendance $existing = null): array
    {
        if (isset($validated['leaves']) && is_array($validated['leaves'])) {
            $result = [];
            foreach ($validated['leaves'] as $code => $days) {
                $result[strtoupper((string) $code)] = (float) $days;
            }

            return $result;
        }

        return [
            'CL' => (float) ($validated['cl'] ?? $existing?->casual_leave ?? 0),
            'EL' => (float) ($validated['el'] ?? $existing?->earned_leave ?? 0),
            'SL' => (float) ($validated['sl'] ?? $existing?->sick_leave ?? 0),
        ];
    }

    /**
     * @param  array<string, float>  $leaveDays
     * @return array<string, mixed>
     */
    private function buildPayload(
        array $validated,
        ?MonthlyAttendance $existing = null,
        array $leaveDays = [],
        ?\App\Models\Employee $employee = null,
        ?\App\Models\AttendancePolicy $policy = null,
    ): array {
        $cl = $leaveDays['CL'] ?? 0;
        $el = $leaveDays['EL'] ?? 0;
        $sl = $leaveDays['SL'] ?? 0;
        $allLeaveDays = array_sum($leaveDays);
        $esiLeave = (float) ($validated['esi_leave'] ?? $existing?->esi_la ?? 0);
        $holiday = (float) ($validated['holiday'] ?? $existing?->holiday ?? 0);
        $totalDays = (float) ($validated['tot_dys'] ?? $existing?->total_days ?? 0);
        $explicitWorkingDays = isset($validated['working_days']) ? (float) $validated['working_days'] : null;

        if ($policy && $employee) {
            $workingDays = app(AttendancePolicyResolver::class)->resolveMonthlyWorkingDays(
                $employee,
                (int) $validated['month'],
                (int) $validated['year'],
                $policy,
            );
        } else {
            $workingDays = $explicitWorkingDays ?? ($totalDays > 0 ? $totalDays : (float) ($existing?->working_days ?? 26));
        }

        if ($policy && $holiday <= 0 && $employee) {
            $holiday = (float) app(HolidayCalendarService::class)->countHolidaysForMonth(
                (int) $employee->company_id,
                (int) $validated['month'],
                (int) $validated['year'],
            );
        }

        $lopDays = 0.0;
        if ($employee) {
            foreach ($leaveDays as $code => $days) {
                $lt = LeaveType::where('company_id', $employee->company_id)->where('code', $code)->first();
                if (! ($lt?->is_paid ?? false)) {
                    $lopDays += $days;
                }
            }
        } else {
            $lopDays = $allLeaveDays;
        }

        $workedDays = max(0, $workingDays - ($lopDays + $esiLeave + $holiday));

        return [
            'policy_id' => $policy?->id ?? $existing?->policy_id,
            'entry_source' => 'manual',
            'holiday_days' => $holiday,
            'total_days' => $totalDays > 0 ? $totalDays : $workingDays,
            'working_days' => $workingDays,
            'present_days' => $workedDays,
            'paid_leave_days' => ($leaveDays['CL'] ?? 0) + ($leaveDays['EL'] ?? 0) + ($leaveDays['SL'] ?? 0),
            'lop_days' => $lopDays,
            'esi_la' => $esiLeave,
            'worked_days' => $workedDays,
            'overtime_days' => (float) ($validated['overtime_days'] ?? $existing?->overtime_days ?? 0),
            'overtime_hours' => (float) ($validated['overtime_hours'] ?? $existing?->overtime_hours ?? 0),
            'shift_code' => $validated['shift_code'] ?? $existing?->shift_code,
        ];
    }
}
