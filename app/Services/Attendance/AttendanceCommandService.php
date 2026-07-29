<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceEntrySource;
use App\Models\Employee;
use App\Models\EmployeeAttendanceSummary;
use App\Models\MonthlyAttendance;
use App\Services\Attendance\Policy\AttendancePolicyContextFactory;
use App\Services\Attendance\Policy\AttendancePolicyEnforcementPipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCommandService
{
    public function __construct(
        private readonly AttendancePolicyContextFactory $contextFactory,
        private readonly AttendancePolicyEnforcementPipeline $pipeline,
        private readonly DailyAttendanceService $dailyAttendanceService,
        private readonly AttendanceService $attendanceService,
        private readonly AttendanceSummaryAggregationService $aggregationService,
        private readonly AttendanceSummaryCalculator $summaryCalculator,
        private readonly LeaveBalanceResolver $leaveBalanceResolver,
        private readonly AttendanceAuditService $auditService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{saved: int, aggregated: int, warnings: list<string>}
     */
    public function saveDaily(int $companyId, int $month, int $year, array $rows, bool $allowOverride = false): array
    {
        $saved = 0;
        $aggregated = 0;
        $warnings = [];
        $employeeIds = [];

        DB::transaction(function () use ($companyId, $month, $year, $rows, $allowOverride, &$saved, &$aggregated, &$warnings, &$employeeIds) {
            $grouped = collect($rows)->groupBy('employee_id');

            foreach ($grouped as $employeeId => $employeeRows) {
                $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);
                $context = $this->contextFactory->forEmployeeMonth($employee, $month, $year);
                $normalized = $this->pipeline->validateDailyRows($context, $employeeRows->all(), $allowOverride);

                $before = $this->snapshotDaily($employee, $month, $year);
                $count = $this->dailyAttendanceService->persistValidatedRows($companyId, $normalized);
                $saved += $count;

                $summary = $this->aggregationService->aggregateMonthForEmployee($employee, $month, $year);
                if ($summary) {
                    $aggregated++;
                }

                $this->leaveBalanceResolver->rebuildBalancesFromDailyRecords($employee, $year);
                $employeeIds[$employee->id] = $employee->id;

                $this->auditService->log(
                    $companyId,
                    'employee_attendance',
                    $employee->id,
                    'daily_saved',
                    $before,
                    $this->snapshotDaily($employee, $month, $year),
                    ['month' => $month, 'year' => $year, 'rows' => $count],
                );
            }
        });

        return compact('saved', 'aggregated', 'warnings');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  keyed by employee_id
     */
    public function saveMonthly(int $companyId, int $month, int $year, array $rows, bool $allowOverride = false): int
    {
        $saved = 0;

        DB::transaction(function () use ($companyId, $month, $year, $rows, $allowOverride, &$saved) {
            foreach ($rows as $employeeId => $data) {
                $payload = array_merge($data, [
                    'employee_id' => (int) $employeeId,
                    'month' => $month,
                    'year' => $year,
                ]);

                $this->upsertMonthlyRecord($companyId, $payload, $allowOverride);
                $saved++;
            }
        });

        return $saved;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>}
     */
    public function importMonthly(int $companyId, array $records, bool $allowOverride = false): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $employeeId = (int) ($record['employee_id'] ?? 0);
                $month = (int) ($record['month'] ?? 0);
                $year = (int) ($record['year'] ?? 0);

                $existing = $employeeId > 0
                    ? MonthlyAttendance::where('company_id', $companyId)
                        ->where('employee_id', $employeeId)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->exists()
                    : false;

                $this->upsertMonthlyRecord($companyId, $record, $allowOverride);

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
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
     * @param  array<string, mixed>  $data
     * @return array{action: string, attendance: array<string, mixed>}
     */
    public function upsertMonthlyRecord(int $companyId, array $data, bool $allowOverride = false): array
    {
        $normalized = $this->attendanceService->validateAgentDataPublic($companyId, $data);
        $employee = app(\App\Services\Employee\EmployeeService::class)->findForCompany(
            $companyId,
            $normalized['employee_id'] ?? null,
            $normalized['employee_code'] ?? null,
        );

        if (! $employee) {
            throw ValidationException::withMessages(['employee' => 'Employee not found.']);
        }

        $month = (int) $normalized['month'];
        $year = (int) $normalized['year'];
        $existing = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $context = $this->contextFactory->forEmployeeMonth($employee, $month, $year);
        $pipelineResult = $this->pipeline->validateMonthlyPayload($context, array_merge($normalized, [
            'employee_id' => $employee->id,
        ]), $allowOverride);

        $validated = $pipelineResult['validated'];
        $leaveDays = $pipelineResult['leave_days'];
        $calculated = $this->summaryCalculator->fromMonthlyPayload(
            $context,
            $validated,
            $leaveDays,
            isset($validated['esi_leave']) ? (float) $validated['esi_leave'] : null,
            isset($validated['holiday']) ? (float) $validated['holiday'] : null,
        );

        $before = $existing ? $this->attendanceService->toSummary($existing) : null;
        $record = $this->attendanceService->persistMonthlySummary(
            $companyId,
            $employee,
            $month,
            $year,
            $validated,
            $calculated,
            $leaveDays,
            $context->policy,
            $existing,
        );

        $this->auditService->log(
            $companyId,
            'employee_attendance_summary',
            $record->id,
            $existing ? 'monthly_saved' : 'monthly_created',
            $before,
            $this->attendanceService->toSummary($record),
            ['month' => $month, 'year' => $year],
        );

        return [
            'action' => $existing ? 'updated' : 'created',
            'attendance' => $this->attendanceService->toSummary($record),
        ];
    }

    public function aggregateEmployeeMonth(Employee $employee, int $month, int $year): ?EmployeeAttendanceSummary
    {
        $summary = $this->aggregationService->aggregateMonthForEmployee($employee, $month, $year);
        if ($summary) {
            $this->leaveBalanceResolver->rebuildBalancesFromDailyRecords($employee, $year);
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotDaily(Employee $employee, int $month, int $year): array
    {
        $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $end = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return \App\Models\EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->map(fn ($r) => [
                'date' => $r->attendance_date->toDateString(),
                'status' => $r->attendance_status->value,
                'leave_type_id' => $r->leave_type_id,
            ])
            ->all();
    }
}
