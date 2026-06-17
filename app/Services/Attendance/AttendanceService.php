<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Services\Employee\EmployeeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function search(int $companyId, int $month, int $year, array $filters = [], int $limit = 50): Collection
    {
        if (!Schema::hasTable('attendance')) {
            return collect();
        }

        $query = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['employee.department', 'employee.designation']);

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
        if (!Schema::hasTable('attendance')) {
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
            ->with(['employee.department', 'employee.designation'])
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function upsertFromAgent(int $companyId, array $data): array
    {
        $validated = $this->validateAgentData($companyId, $data, true);

        $employee = app(EmployeeService::class)->findForCompany(
            $companyId,
            $validated['employee_id'] ?? null,
            $validated['employee_code'] ?? null,
        );

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => 'Employee not found.']);
        }

        $existing = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        $payload = $this->buildPayload($validated, $existing);
        $record = MonthlyAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            $payload,
        );

        $record->load(['employee.department', 'employee.designation']);

        return [
            'action' => $existing ? 'updated' : 'created',
            'attendance' => $this->toSummary($record),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>}
     */
    public function bulkUpsertFromAgent(int $companyId, array $records): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $result = $this->upsertFromAgent($companyId, $record);
                if ($result['action'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (ValidationException $e) {
                $failed++;
                $errors[$index] = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            } catch (\Throwable $e) {
                $failed++;
                $errors[$index] = $e->getMessage();
            }
        }

        return compact('created', 'updated', 'failed', 'errors');
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
        $deductions = is_array($record->deductions ?? null) ? $record->deductions : [];
        if (empty($deductions)) {
            $deductions = array_values(array_filter([
                $record->ded_1 ?? null,
                $record->ded_2 ?? null,
                $record->ded_3 ?? null,
            ], fn ($v) => $v !== null));
        }

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
            'deductions' => array_map('floatval', $deductions),
        ];
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
            if ($requireEmployee && empty($normalized['employee_id']) && empty($normalized['employee_code'])) {
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

        for ($i = 1; $i <= 3; $i++) {
            $key = 'ded_' . $i;
            if (isset($normalized[$key]) && !isset($normalized['deductions'])) {
                $normalized['deductions'] = [];
            }
            if (isset($normalized[$key])) {
                $normalized['deductions'][$i - 1] = $normalized[$key];
            }
        }

        if (isset($normalized['deductions']) && is_array($normalized['deductions'])) {
            $normalized['deductions'] = array_values($normalized['deductions']);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function buildPayload(array $validated, ?MonthlyAttendance $existing = null): array
    {
        $cl = (float) ($validated['cl'] ?? $existing?->casual_leave ?? 0);
        $el = (float) ($validated['el'] ?? $existing?->earned_leave ?? 0);
        $sl = (float) ($validated['sl'] ?? $existing?->sick_leave ?? 0);
        $esiLeave = (float) ($validated['esi_leave'] ?? $existing?->esi_la ?? 0);
        $holiday = (float) ($validated['holiday'] ?? $existing?->holiday ?? 0);
        $totalDays = (float) ($validated['tot_dys'] ?? $existing?->total_days ?? 0);
        $workedDays = max(0, $totalDays - ($cl + $el + $sl + $esiLeave + $holiday));

        $deductions = array_key_exists('deductions', $validated)
            ? array_values(array_map(fn ($v) => (float) $v, $validated['deductions'] ?? []))
            : (is_array($existing?->deductions) ? $existing->deductions : []);

        if (empty($deductions) && $existing) {
            $deductions = array_values(array_filter([
                $existing->ded_1 ?? null,
                $existing->ded_2 ?? null,
                $existing->ded_3 ?? null,
            ], fn ($v) => $v !== null));
        }

        return [
            'casual_leave' => $cl,
            'earned_leave' => $el,
            'sick_leave' => $sl,
            'esi_la' => $esiLeave,
            'holiday' => $holiday,
            'total_days' => $totalDays,
            'worked_days' => $workedDays,
            'overtime_days' => (float) ($validated['overtime_days'] ?? $existing?->overtime_days ?? 0),
            'overtime_hours' => (float) ($validated['overtime_hours'] ?? $existing?->overtime_hours ?? 0),
            'shift_code' => $validated['shift_code'] ?? $existing?->shift_code,
            'deductions' => $deductions,
            'ded_1' => $deductions[0] ?? 0,
            'ded_2' => $deductions[1] ?? 0,
            'ded_3' => $deductions[2] ?? 0,
        ];
    }
}
