<?php

namespace App\Services\Ess;

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Leave\LeaveDayPortion;
use App\Enums\Leave\LeaveRequestStatus;
use App\Enums\Permission;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\AttendanceSummaryAggregationService;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Attendance\LeaveAttendanceSyncService;
use App\Services\Attendance\LeaveBalanceResolver;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(
        private readonly AttendancePolicyResolver $policyResolver,
        private readonly LeaveBalanceResolver $balanceResolver,
        private readonly DailyAttendanceService $dailyAttendance,
        private readonly LeaveAttendanceSyncService $leaveAttendanceSync,
        private readonly AttendanceSummaryAggregationService $summaryAggregation,
    ) {}

    /**
     * @param  array{
     *     leave_type_id: int,
     *     start_date: string,
     *     end_date: string,
     *     day_portion?: string,
     *     reason?: string|null
     * }  $input
     */
    public function submit(Employee $employee, array $input): LeaveRequest
    {
        $leaveType = LeaveType::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->findOrFail((int) $input['leave_type_id']);

        $portion = LeaveDayPortion::from($input['day_portion'] ?? LeaveDayPortion::Full->value);
        $start = Carbon::parse($input['start_date'])->startOfDay();
        $end = Carbon::parse($input['end_date'])->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after the start date.',
            ]);
        }

        if ($portion !== LeaveDayPortion::Full && ! $start->equalTo($end)) {
            throw ValidationException::withMessages([
                'day_portion' => 'Half-day leave can only be applied for a single date.',
            ]);
        }

        $workingDates = $this->workingDates($employee, $start, $end);
        if ($workingDates === []) {
            throw ValidationException::withMessages([
                'start_date' => 'Selected range has no working days (week offs / holidays only).',
            ]);
        }

        $days = $portion === LeaveDayPortion::Full
            ? (float) count($workingDates)
            : $portion->dayValue();

        $this->assertNoOverlap($employee, $start, $end);
        $this->assertBalance($employee, $leaveType, $days, (int) $start->year);

        return LeaveRequest::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'day_portion' => $portion,
            'days' => $days,
            'reason' => isset($input['reason']) ? trim((string) $input['reason']) : null,
            'status' => LeaveRequestStatus::Pending,
        ]);
    }

    public function cancel(LeaveRequest $request, Employee $actor): LeaveRequest
    {
        if ((int) $request->employee_id !== (int) $actor->id) {
            abort(403, 'You can only cancel your own leave requests.');
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be cancelled.',
            ]);
        }

        $request->forceFill([
            'status' => LeaveRequestStatus::Cancelled,
            'reviewed_at' => now(),
            'review_note' => 'Cancelled by employee',
        ])->save();

        return $request->fresh(['leaveType', 'employee']);
    }

    public function approve(LeaveRequest $request, User $reviewer, ?string $note = null): LeaveRequest
    {
        $this->assertCanReview($request, $reviewer);

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be approved.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $note) {
            $request->refresh();
            $employee = $request->employee()->lockForUpdate()->firstOrFail();
            $leaveType = $request->leaveType()->firstOrFail();

            $this->assertBalance($employee, $leaveType, (float) $request->days, (int) $request->start_date->year);
            $this->writeAttendanceMarks($request);

            $request->forceFill([
                'status' => LeaveRequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            return $request->fresh(['leaveType', 'employee', 'reviewer']);
        });
    }

    public function reject(LeaveRequest $request, User $reviewer, ?string $note = null): LeaveRequest
    {
        $this->assertCanReview($request, $reviewer);

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be rejected.',
            ]);
        }

        $request->forceFill([
            'status' => LeaveRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        return $request->fresh(['leaveType', 'employee', 'reviewer']);
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function pendingForApprover(User $approver, int $companyId): Collection
    {
        $query = LeaveRequest::query()
            ->with(['employee.department', 'leaveType'])
            ->where('company_id', $companyId)
            ->where('status', LeaveRequestStatus::Pending);

        if ($approver->hasPermission(Permission::EssApprovalsAny)) {
            return $query->orderBy('start_date')->get();
        }

        $managerEmployee = Employee::query()
            ->where('user_id', $approver->id)
            ->where('company_id', $companyId)
            ->first();

        if (! $managerEmployee) {
            return collect();
        }

        return $query
            ->whereHas('employee', fn ($q) => $q->where('manager_id', $managerEmployee->id))
            ->orderBy('start_date')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function workingDates(Employee $employee, Carbon $start, Carbon $end): array
    {
        $policy = $this->policyResolver->resolveForEmployee($employee);
        $dates = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            /** @var Carbon $day */
            $dates[] = $day->toDateString();
        }

        if (! $policy) {
            return $dates;
        }

        $byMonth = collect($dates)->groupBy(fn (string $d) => Carbon::parse($d)->format('Y-m'));
        $working = [];

        foreach ($byMonth as $yearMonth => $monthDates) {
            [$year, $month] = array_map('intval', explode('-', $yearMonth));
            $defaults = app(\App\Services\Attendance\AttendanceCalendarService::class)
                ->defaultStatusesForPeriod($employee, $policy, $month, $year, $monthDates->all());

            foreach ($monthDates as $date) {
                $auto = $defaults[$date]['status'] ?? '';
                if (in_array($auto, [AttendanceStatus::WEEK_OFF->value, AttendanceStatus::HOLIDAY->value], true)) {
                    continue;
                }
                $working[] = $date;
            }
        }

        return $working;
    }

    private function assertNoOverlap(Employee $employee, Carbon $start, Carbon $end): void
    {
        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have a pending or approved leave overlapping these dates.',
            ]);
        }
    }

    private function assertBalance(Employee $employee, LeaveType $leaveType, float $days, int $year): void
    {
        $policy = $this->policyResolver->resolveForEmployee($employee);
        if (! $policy) {
            return;
        }

        $this->balanceResolver->assertSufficientBalance(
            $employee,
            $policy,
            [strtoupper($leaveType->code) => $days],
            $year,
        );
    }

    private function assertCanReview(LeaveRequest $request, User $reviewer): void
    {
        if ($reviewer->hasPermission(Permission::EssApprovalsAny)) {
            return;
        }

        if (! $reviewer->hasPermission(Permission::EssApprovals)) {
            abort(403, 'You are not allowed to review leave requests.');
        }

        $managerEmployee = Employee::query()
            ->where('user_id', $reviewer->id)
            ->where('company_id', $request->company_id)
            ->first();

        $request->loadMissing('employee');

        if (! $managerEmployee || (int) $request->employee->manager_id !== (int) $managerEmployee->id) {
            abort(403, 'You can only approve leave for your direct reports.');
        }
    }

    private function writeAttendanceMarks(LeaveRequest $request): void
    {
        $employee = $request->employee;
        $workingDates = $this->workingDates(
            $employee,
            $request->start_date->copy()->startOfDay(),
            $request->end_date->copy()->startOfDay(),
        );

        $portion = $request->day_portion;
        $approvedMarks = [];

        foreach ($workingDates as $date) {
            if ($portion === LeaveDayPortion::Full) {
                $approvedMarks[] = [
                    'date' => $date,
                    'leave_type_id' => $request->leave_type_id,
                    'attendance_status' => AttendanceStatus::LEAVE->value,
                ];
            } else {
                $approvedMarks[] = [
                    'date' => $date,
                    'leave_type_id' => $request->leave_type_id,
                    'attendance_status' => AttendanceStatus::HALF_DAY->value,
                    'first_half_status' => $portion === LeaveDayPortion::FirstHalf
                        ? AttendanceStatus::LEAVE->value
                        : AttendanceStatus::PRESENT->value,
                    'second_half_status' => $portion === LeaveDayPortion::SecondHalf
                        ? AttendanceStatus::LEAVE->value
                        : AttendanceStatus::PRESENT->value,
                ];
            }
        }

        $rows = $this->leaveAttendanceSync->buildRowsFromApprovedMarks($employee, $approvedMarks);

        $this->dailyAttendance->persistValidatedRows((int) $request->company_id, $rows);

        $years = collect($rows)
            ->map(fn (array $row) => (int) Carbon::parse($row['attendance_date'])->year)
            ->unique();

        foreach ($years as $year) {
            $this->balanceResolver->rebuildBalancesFromDailyRecords($employee, $year);
        }

        $months = collect($rows)
            ->map(fn (array $row) => Carbon::parse($row['attendance_date']))
            ->unique(fn (Carbon $d) => $d->format('Y-n'));

        foreach ($months as $day) {
            $this->summaryAggregation->aggregateMonthForEmployee(
                $employee,
                (int) $day->month,
                (int) $day->year,
            );
        }
    }
}
