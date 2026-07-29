<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Services\Settings\Adapters\AttendanceSettingsAdapter;
use Illuminate\Support\Collection;

class MonthLockAndReconciliationService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly AttendanceAuditService $auditService,
        private readonly AttendanceSettingsAdapter $settingsAdapter,
    ) {}

    public function isMonthLockEnforced(int $companyId): bool
    {
        return $this->settingsAdapter->shouldEnforceMonthLock($companyId);
    }

    public function lockMonthForEmployee(Employee $employee, int $month, int $year): void
    {
        if (! $this->isMonthLockEnforced((int) $employee->company_id)) {
            throw new \InvalidArgumentException('Month lock is disabled in company settings.');
        }
        $summary = MonthlyAttendance::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (! $summary) {
            throw new \InvalidArgumentException('No attendance summary found to lock.');
        }

        if ($this->attendanceService->isSummaryLocked($summary)) {
            return;
        }

        $before = $this->attendanceService->toSummary($summary);
        $this->attendanceService->lockSummary($summary);
        $summary->refresh();

        $this->auditService->log(
            (int) $employee->company_id,
            'employee_attendance_summary',
            $summary->id,
            'summary_locked',
            $before,
            $this->attendanceService->toSummary($summary),
            ['month' => $month, 'year' => $year],
        );
    }

    public function lockMonthForCompany(int $companyId, int $month, int $year): int
    {
        $locked = 0;
        $summaries = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('locked_at')
            ->with('employee')
            ->get();

        foreach ($summaries as $summary) {
            if ($summary->employee) {
                $this->lockMonthForEmployee($summary->employee, $month, $year);
                $locked++;
            }
        }

        return $locked;
    }

    /**
     * @return array{
     *     total: int,
     *     consistent: int,
     *     conflicts: list<array{employee_id: int, employee_code: string|null, message: string}>
     * }
     */
    public function reconcileCompanyMonth(int $companyId, int $month, int $year): array
    {
        $summaries = MonthlyAttendance::query()
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get();

        $consistent = 0;
        $conflicts = [];

        foreach ($summaries as $summary) {
            $result = $this->attendanceService->reconcileSummary($summary);
            if ($result['consistent']) {
                $consistent++;
            } else {
                $conflicts[] = [
                    'employee_id' => $summary->employee_id,
                    'employee_code' => $summary->employee?->employee_code,
                    'message' => $result['message'] ?? 'Inconsistent summary.',
                ];
            }
        }

        return [
            'total' => $summaries->count(),
            'consistent' => $consistent,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @return array{
     *     ready: bool,
     *     unlocked_summaries: Collection,
     *     reconciliation_conflicts: list<array{employee_id: int, employee_code: string|null, message: string}>,
     *     messages: list<string>
     * }
     */
    public function assertReadyForPayroll(PayrollRun $run): array
    {
        $periodEnd = \Carbon\Carbon::create($run->year, $run->month)->endOfMonth();
        $employees = Employee::query()
            ->where('company_id', $run->company_id)
            ->where(function ($query) use ($periodEnd) {
                $query->whereNull('dol')->orWhere('dol', '>=', $periodEnd->copy()->startOfMonth());
            })
            ->get();

        $unlocked = collect();
        $messages = [];

        foreach ($employees as $employee) {
            $summary = MonthlyAttendance::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $run->company_id)
                ->where('month', $run->month)
                ->where('year', $run->year)
                ->first();

            if (! $summary) {
                continue;
            }

            if (! $this->attendanceService->isSummaryLocked($summary)) {
                $unlocked->push($employee);
            }
        }

        $reconciliation = $this->reconcileCompanyMonth($run->company_id, $run->month, $run->year);

        if ($unlocked->isNotEmpty()) {
            $messages[] = $unlocked->count().' employee(s) have unlocked attendance summaries.';
        }

        if ($reconciliation['conflicts'] !== []) {
            $messages[] = count($reconciliation['conflicts']).' attendance reconciliation conflict(s) found.';
        }

        return [
            'ready' => $unlocked->isEmpty() && $reconciliation['conflicts'] === [],
            'unlocked_summaries' => $unlocked,
            'reconciliation_conflicts' => $reconciliation['conflicts'],
            'messages' => $messages,
        ];
    }
}
