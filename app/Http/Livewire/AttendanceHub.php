<?php

namespace App\Http\Livewire;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Services\Attendance\AttendanceCommandService;
use App\Services\Attendance\AttendanceMonthlyMatrixService;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Attendance\HolidayCalendarService;
use App\Services\Attendance\LeaveExceptionRuleService;
use App\Services\Attendance\LeaveTypeService;
use App\Services\Attendance\MonthLockAndReconciliationService;
use App\Services\Settings\Adapters\AttendanceSettingsAdapter;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceTemplateExport;

class AttendanceHub extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    public string $companyId = '';
    public string $activeTab = 'policies';

    // Policies
    public bool $showPolicyModal = false;
    public ?int $editingPolicyId = null;
    public string $policyName = '';
    public string $attendanceMode = 'monthly_summary';
    public string $workingDaysBasis = 'fixed_26';
    public $customWorkingDays = '';
    public string $weeklyOffRule = 'sunday';
    /** @var list<int> */
    public array $customWeeklyOffDays = [];
    /** @var list<int> */
    public array $alternateSaturdayWeeks = [2, 4];
    public bool $paidHolidays = true;
    public bool $paidWeeklyOffs = true;
    public bool $requireAttendanceBeforeHoliday = false;
    public bool $requireAttendanceAfterHoliday = false;
    public bool $autoAdjustApprovedLeave = false;
    public bool $sandwichLeaveEnabled = false;
    public bool $compOffEnabled = false;
    public string $leaveBalancePriorityMode = 'policy_order';
    public bool $autoApplyWeekOffs = true;
    public bool $autoApplyHolidays = true;
    public bool $includePublicHolidays = true;
    public bool $includeRegionalHolidays = true;
    public bool $includeEmergencyHolidays = true;
    public int $graceMinutes = 0;
    public int $minHalfDayMinutes = 240;
    public int $minFullDayMinutes = 480;
    public bool $allowOvertime = false;
    public bool $allowHalfDay = true;
    public bool $allowNegativeLeaveBalance = false;
    public bool $policyIsActive = true;
    /** @var list<array{leave_type_id: string, annual_quota: string, carry_forward_limit: string, encashable: bool, deduction_priority: string}> */
    public array $policyLeaveRows = [];

    // Holidays
    public string $holidayDate = '';
    public string $holidayName = '';
    public string $holidaySourceType = 'company';
    public string $holidayLocationId = '';
    public bool $holidayIsPaid = true;
    public bool $holidayIsActive = true;
    public ?int $editingHolidayId = null;
    public int $holidayFilterMonth;
    public int $holidayFilterYear;

    // Assignments
    public string $assignmentScopeType = 'company';
    /** @var list<int|string> */
    public array $assignmentScopeIds = [];
    public string $assignmentPolicyId = '';
    public string $assignmentEffectiveFrom = '';
    public string $assignmentEffectiveTo = '';
    /** @var array<string, mixed> */
    public array $inheritanceChain = [];

    // Monthly entry
    public int $month;
    public int $year;
    public string $selectedLocation = '';
    public string $selectedDepartment = '';
    public string $selectedDesignation = '';
    /** @var array<int, array<string, mixed>> */
    public array $monthlyData = [];
    public bool $monthlyEditMode = false;
    public $excel_file;

    protected function rules(): array
    {
        return [
            'excel_file' => 'nullable|file|mimes:xlsx,xls|max:10240',
        ];
    }

    // Daily marking
    public int $dailyMonth;
    public int $dailyYear;
    public int $dailyRangeStart = 1;
    public int $dailyRangeEnd = 7;
    public string $dailyLocation = '';
    public string $dailyDepartment = '';
    public string $dailyDesignation = '';
    /** @var array<int, array<string, array<string, string>>> employeeId => date => {status, leave_type_id} */
    public array $dailyMatrix = [];
    public bool $dailyEditMode = false;
    public string $bulkStatus = 'present';

    // Leave types
    public bool $showLeaveTypeModal = false;
    public ?int $editingLeaveTypeId = null;
    public string $leaveTypeName = '';
    public string $leaveTypeCode = '';
    public bool $leaveTypeIsPaid = true;
    public string $leaveTypeAnnualQuota = '0';
    public bool $leaveTypeCarryForward = false;
    public bool $leaveTypeEncashable = false;
    public bool $leaveTypeIsActive = true;

    // Exception rules
    public bool $showExceptionRuleModal = false;
    public ?int $editingExceptionRuleId = null;
    public string $exceptionPolicyId = '';
    public string $exceptionLeaveTypeId = '';
    public string $exceptionMaxPerMonth = '';
    public string $exceptionMaxPerYear = '';
    public string $exceptionOnExceed = 'block';
    public string $exceptionPayrollAction = 'none';
    public bool $exceptionIsActive = true;

    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id ?? (string) session()->get('companyId', '');
        if ($this->companyId !== '') {
            session()->put('companyId', $this->companyId);
        }

        $this->month = now()->month;
        $this->year = now()->year;
        $this->dailyMonth = now()->month;
        $this->dailyYear = now()->year;
        $this->assignmentEffectiveFrom = now()->toDateString();
        $this->dailyRangeEnd = min(7, Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1)->daysInMonth);
        $this->holidayFilterMonth = now()->month;
        $this->holidayFilterYear = now()->year;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();

        if ($tab === 'monthly') {
            $this->dispatchBrowserEvent('attendance-monthly-tab-shown');
        }
    }

    public function canManage(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermission('attendance.manage');
    }

    // --- Policies ---

    public function openPolicyModal(?int $policyId = null): void
    {
        $this->resetPolicyForm();
        $this->editingPolicyId = $policyId;

        if ($policyId) {
            $policy = app(AttendancePolicyService::class)
                ->listForCompany((int) $this->companyId)
                ->firstWhere('id', $policyId);

            if ($policy) {
                $this->policyName = $policy->policy_name;
                $this->attendanceMode = $policy->attendance_mode->value;
                $this->workingDaysBasis = $policy->working_days_basis->value;
                $this->customWorkingDays = $policy->custom_working_days ?? '';
                $this->weeklyOffRule = $policy->weekly_off_rule->value;
                $this->customWeeklyOffDays = $policy->custom_weekly_off_days ?? [];
                $this->alternateSaturdayWeeks = $policy->alternate_saturday_weeks ?? [2, 4];
                $this->paidHolidays = $policy->paid_holidays;
                $this->paidWeeklyOffs = $policy->paid_weekly_offs;
                $this->requireAttendanceBeforeHoliday = $policy->require_attendance_before_holiday;
                $this->requireAttendanceAfterHoliday = $policy->require_attendance_after_holiday;
                $this->autoAdjustApprovedLeave = $policy->auto_adjust_approved_leave;
                $this->sandwichLeaveEnabled = $policy->sandwich_leave_enabled;
                $this->compOffEnabled = $policy->comp_off_enabled;
                $this->leaveBalancePriorityMode = $policy->leave_balance_priority_mode->value;
                $this->autoApplyWeekOffs = $policy->auto_apply_week_offs;
                $this->autoApplyHolidays = $policy->auto_apply_holidays;
                $this->includePublicHolidays = $policy->include_public_holidays;
                $this->includeRegionalHolidays = $policy->include_regional_holidays;
                $this->includeEmergencyHolidays = $policy->include_emergency_holidays;
                $this->graceMinutes = $policy->grace_minutes;
                $this->minHalfDayMinutes = $policy->min_half_day_minutes;
                $this->minFullDayMinutes = $policy->min_full_day_minutes;
                $this->allowOvertime = $policy->allow_overtime;
                $this->allowHalfDay = $policy->allow_half_day;
                $this->allowNegativeLeaveBalance = $policy->allow_negative_leave_balance;
                $this->policyIsActive = $policy->is_active;
                $this->policyLeaveRows = $policy->policyLeaveTypes->map(fn($row) => [
                    'leave_type_id' => (string) $row->leave_type_id,
                    'annual_quota' => (string) $row->annual_quota,
                    'carry_forward_limit' => $row->carry_forward_limit !== null ? (string) $row->carry_forward_limit : '',
                    'encashable' => $row->encashable,
                    'deduction_priority' => (string) ($row->deduction_priority ?? 100),
                ])->values()->all();
            }
        }

        if (empty($this->policyLeaveRows)) {
            $this->addPolicyLeaveRow();
        }

        $this->showPolicyModal = true;
    }

    public function addPolicyLeaveRow(): void
    {
        $this->policyLeaveRows[] = [
            'leave_type_id' => '',
            'annual_quota' => '0',
            'carry_forward_limit' => '',
            'encashable' => false,
            'deduction_priority' => '100',
        ];
    }

    public function removePolicyLeaveRow(int $index): void
    {
        unset($this->policyLeaveRows[$index]);
        $this->policyLeaveRows = array_values($this->policyLeaveRows);
    }

    public function savePolicy(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $service = app(AttendancePolicyService::class);
        $payload = [
            'policy_name' => $this->policyName,
            'attendance_mode' => $this->attendanceMode,
            'working_days_basis' => $this->workingDaysBasis,
            'custom_working_days' => $this->customWorkingDays !== '' ? $this->customWorkingDays : null,
            'weekly_off_rule' => $this->weeklyOffRule,
            'custom_weekly_off_days' => $this->weeklyOffRule === 'custom' ? $this->customWeeklyOffDays : null,
            'alternate_saturday_weeks' => $this->weeklyOffRule === 'alternate_saturday' ? $this->alternateSaturdayWeeks : null,
            'paid_holidays' => $this->paidHolidays,
            'paid_weekly_offs' => $this->paidWeeklyOffs,
            'require_attendance_before_holiday' => $this->requireAttendanceBeforeHoliday,
            'require_attendance_after_holiday' => $this->requireAttendanceAfterHoliday,
            'auto_adjust_approved_leave' => $this->autoAdjustApprovedLeave,
            'sandwich_leave_enabled' => $this->sandwichLeaveEnabled,
            'comp_off_enabled' => $this->compOffEnabled,
            'leave_balance_priority_mode' => $this->leaveBalancePriorityMode,
            'auto_apply_week_offs' => $this->autoApplyWeekOffs,
            'auto_apply_holidays' => $this->autoApplyHolidays,
            'include_public_holidays' => $this->includePublicHolidays,
            'include_regional_holidays' => $this->includeRegionalHolidays,
            'include_emergency_holidays' => $this->includeEmergencyHolidays,
            'grace_minutes' => $this->graceMinutes,
            'min_half_day_minutes' => $this->minHalfDayMinutes,
            'min_full_day_minutes' => $this->minFullDayMinutes,
            'allow_overtime' => $this->allowOvertime,
            'allow_half_day' => $this->allowHalfDay,
            'allow_negative_leave_balance' => $this->allowNegativeLeaveBalance,
            'is_active' => $this->policyIsActive,
        ];

        $leaveRows = collect($this->policyLeaveRows)
            ->filter(fn($r) => !empty($r['leave_type_id']))
            ->map(fn($r) => [
                'leave_type_id' => (int) $r['leave_type_id'],
                'annual_quota' => (float) ($r['annual_quota'] ?? 0),
                'carry_forward_limit' => $r['carry_forward_limit'] !== '' ? (float) $r['carry_forward_limit'] : null,
                'encashable' => (bool) ($r['encashable'] ?? false),
                'deduction_priority' => (int) ($r['deduction_priority'] ?? 100),
            ])
            ->values()
            ->all();

        try {
            if ($this->editingPolicyId) {
                $service->update((int) $this->companyId, $this->editingPolicyId, $payload, $leaveRows);
                session()->flash('success', 'Policy updated successfully.');
            } else {
                $service->create((int) $this->companyId, $payload, $leaveRows);
                session()->flash('success', 'Policy created successfully.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('policy_' . $field, $messages[0]);
            }

            return;
        }

        $this->showPolicyModal = false;
        $this->resetPolicyForm();
    }

    public function deactivatePolicy(int $policyId): void
    {
        if (!$this->canManage()) {
            return;
        }

        app(AttendancePolicyService::class)->deactivate((int) $this->companyId, $policyId);
        session()->flash('success', 'Policy deactivated.');
    }

    // --- Holidays ---

    public function resetHolidayForm(): void
    {
        $this->editingHolidayId = null;
        $this->holidayDate = '';
        $this->holidayName = '';
        $this->holidaySourceType = 'company';
        $this->holidayLocationId = '';
        $this->holidayIsPaid = true;
        $this->holidayIsActive = true;
    }

    public function editHoliday(int $holidayId): void
    {
        $holiday = \App\Models\CompanyHoliday::where('company_id', $this->companyId)->findOrFail($holidayId);
        $this->editingHolidayId = $holiday->id;
        $this->holidayDate = $holiday->holiday_date->toDateString();
        $this->holidayName = $holiday->name;
        $this->holidaySourceType = $holiday->source_type->value;
        $this->holidayLocationId = $holiday->location_id ? (string) $holiday->location_id : '';
        $this->holidayIsPaid = $holiday->is_paid;
        $this->holidayIsActive = $holiday->is_active;
    }

    public function saveHoliday(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $service = app(HolidayCalendarService::class);
        $payload = [
            'holiday_date' => $this->holidayDate,
            'name' => $this->holidayName,
            'source_type' => $this->holidaySourceType,
            'location_id' => $this->holidayLocationId !== '' ? (int) $this->holidayLocationId : null,
            'is_paid' => $this->holidayIsPaid,
            'is_active' => $this->holidayIsActive,
        ];

        try {
            if ($this->editingHolidayId) {
                $service->update((int) $this->companyId, $this->editingHolidayId, $payload);
                session()->flash('success', 'Holiday updated.');
            } else {
                $service->create((int) $this->companyId, $payload);
                session()->flash('success', 'Holiday added.');
            }
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());

            return;
        }

        $this->resetHolidayForm();
    }

    public function deleteHoliday(int $holidayId): void
    {
        if (!$this->canManage()) {
            return;
        }

        app(HolidayCalendarService::class)->delete((int) $this->companyId, $holidayId);
        session()->flash('success', 'Holiday removed.');
    }

    public function lockMonthlySummary(int $employeeId): void
    {
        if (!$this->canManage()) {
            return;
        }

        $summary = MonthlyAttendance::where('employee_id', $employeeId)
            ->where('company_id', $this->companyId)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->first();

        if ($summary) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                app(MonthLockAndReconciliationService::class)->lockMonthForEmployee($employee, $this->month, $this->year);
                session()->flash('success', 'Attendance summary locked.');
            }
        }
    }

    // --- Leave types ---

    public function openLeaveTypeModal(?int $leaveTypeId = null): void
    {
        if (!$this->canManage()) {
            return;
        }

        $this->resetLeaveTypeForm();
        if ($leaveTypeId) {
            $lt = LeaveType::where('company_id', $this->companyId)->findOrFail($leaveTypeId);
            $this->editingLeaveTypeId = $lt->id;
            $this->leaveTypeName = $lt->name;
            $this->leaveTypeCode = $lt->code;
            $this->leaveTypeIsPaid = $lt->is_paid;
            $this->leaveTypeAnnualQuota = (string) $lt->annual_quota;
            $this->leaveTypeCarryForward = $lt->carry_forward;
            $this->leaveTypeEncashable = $lt->encashable;
            $this->leaveTypeIsActive = $lt->is_active;
        }
        $this->showLeaveTypeModal = true;
    }

    public function saveLeaveType(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $data = [
            'name' => $this->leaveTypeName,
            'code' => $this->leaveTypeCode,
            'is_paid' => $this->leaveTypeIsPaid,
            'annual_quota' => $this->leaveTypeAnnualQuota !== '' ? (float) $this->leaveTypeAnnualQuota : 0,
            'carry_forward' => $this->leaveTypeCarryForward,
            'encashable' => $this->leaveTypeEncashable,
            'is_active' => $this->leaveTypeIsActive,
        ];

        try {
            $service = app(LeaveTypeService::class);
            if ($this->editingLeaveTypeId) {
                $service->update((int) $this->companyId, $this->editingLeaveTypeId, $data);
                session()->flash('success', 'Leave type updated.');
            } else {
                $service->create((int) $this->companyId, $data);
                session()->flash('success', 'Leave type created.');
            }
            $this->showLeaveTypeModal = false;
            $this->resetLeaveTypeForm();
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    public function deactivateLeaveType(int $leaveTypeId): void
    {
        if (!$this->canManage()) {
            return;
        }

        app(LeaveTypeService::class)->deactivate((int) $this->companyId, $leaveTypeId);
        session()->flash('success', 'Leave type deactivated.');
    }

    private function resetLeaveTypeForm(): void
    {
        $this->editingLeaveTypeId = null;
        $this->leaveTypeName = '';
        $this->leaveTypeCode = '';
        $this->leaveTypeIsPaid = true;
        $this->leaveTypeAnnualQuota = '0';
        $this->leaveTypeCarryForward = false;
        $this->leaveTypeEncashable = false;
        $this->leaveTypeIsActive = true;
    }

    // --- Exception rules ---

    public function openExceptionRuleModal(?int $ruleId = null): void
    {
        if (!$this->canManage()) {
            return;
        }

        $this->resetExceptionRuleForm();
        if ($ruleId) {
            $rule = \App\Models\AttendanceLeaveExceptionRule::where('company_id', $this->companyId)->findOrFail($ruleId);
            $this->editingExceptionRuleId = $rule->id;
            $this->exceptionPolicyId = (string) ($rule->policy_id ?? '');
            $this->exceptionLeaveTypeId = (string) ($rule->leave_type_id ?? '');
            $this->exceptionMaxPerMonth = $rule->max_days_per_month !== null ? (string) $rule->max_days_per_month : '';
            $this->exceptionMaxPerYear = $rule->max_days_per_year !== null ? (string) $rule->max_days_per_year : '';
            $this->exceptionOnExceed = $rule->on_exceed->value;
            $this->exceptionPayrollAction = $rule->payroll_action->value;
            $this->exceptionIsActive = $rule->is_active;
        }
        $this->showExceptionRuleModal = true;
    }

    public function saveExceptionRule(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $data = [
            'policy_id' => $this->exceptionPolicyId !== '' ? (int) $this->exceptionPolicyId : null,
            'leave_type_id' => $this->exceptionLeaveTypeId !== '' ? (int) $this->exceptionLeaveTypeId : null,
            'max_days_per_month' => $this->exceptionMaxPerMonth !== '' ? (float) $this->exceptionMaxPerMonth : null,
            'max_days_per_year' => $this->exceptionMaxPerYear !== '' ? (float) $this->exceptionMaxPerYear : null,
            'on_exceed' => $this->exceptionOnExceed,
            'payroll_action' => $this->exceptionPayrollAction,
            'is_active' => $this->exceptionIsActive,
        ];

        try {
            $service = app(LeaveExceptionRuleService::class);
            if ($this->editingExceptionRuleId) {
                $service->update((int) $this->companyId, $this->editingExceptionRuleId, $data);
                session()->flash('success', 'Exception rule updated.');
            } else {
                $service->create((int) $this->companyId, $data);
                session()->flash('success', 'Exception rule created.');
            }
            $this->showExceptionRuleModal = false;
            $this->resetExceptionRuleForm();
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    public function deleteExceptionRule(int $ruleId): void
    {
        if (!$this->canManage()) {
            return;
        }

        app(LeaveExceptionRuleService::class)->delete((int) $this->companyId, $ruleId);
        session()->flash('success', 'Exception rule removed.');
    }

    private function resetExceptionRuleForm(): void
    {
        $this->editingExceptionRuleId = null;
        $this->exceptionPolicyId = '';
        $this->exceptionLeaveTypeId = '';
        $this->exceptionMaxPerMonth = '';
        $this->exceptionMaxPerYear = '';
        $this->exceptionOnExceed = 'block';
        $this->exceptionPayrollAction = 'none';
        $this->exceptionIsActive = true;
    }

    private function resetPolicyForm(): void
    {
        $this->editingPolicyId = null;
        $this->policyName = '';
        $this->attendanceMode = 'monthly_summary';
        $this->workingDaysBasis = 'fixed_26';
        $this->customWorkingDays = '';
        $this->weeklyOffRule = 'sunday';
        $this->customWeeklyOffDays = [];
        $this->alternateSaturdayWeeks = [2, 4];
        $this->paidHolidays = true;
        $this->paidWeeklyOffs = true;
        $this->requireAttendanceBeforeHoliday = false;
        $this->requireAttendanceAfterHoliday = false;
        $this->autoAdjustApprovedLeave = false;
        $this->sandwichLeaveEnabled = false;
        $this->compOffEnabled = false;
        $this->leaveBalancePriorityMode = 'policy_order';
        $this->autoApplyWeekOffs = true;
        $this->autoApplyHolidays = true;
        $this->includePublicHolidays = true;
        $this->includeRegionalHolidays = true;
        $this->includeEmergencyHolidays = true;
        $this->graceMinutes = 0;
        $this->minHalfDayMinutes = 240;
        $this->minFullDayMinutes = 480;
        $this->allowOvertime = false;
        $this->allowHalfDay = true;
        $this->allowNegativeLeaveBalance = false;
        $this->policyIsActive = true;
        $this->policyLeaveRows = [];
        $this->resetErrorBag();
    }

    // --- Assignments ---

    public function updatedAssignmentScopeType(): void
    {
        $this->assignmentScopeIds = [];
        $this->inheritanceChain = [];
    }

    public function updatedAssignmentScopeIds(): void
    {
        $this->loadInheritancePreview();
    }

    public function selectAllAssignmentScopes(array $allIds): void
    {
        $this->assignmentScopeIds = array_map('strval', $allIds);
        $this->loadInheritancePreview();
    }

    public function clearAssignmentScopes(): void
    {
        $this->assignmentScopeIds = [];
        $this->inheritanceChain = [];
    }

    public function loadInheritancePreview(): void
    {
        $this->inheritanceChain = [];

        if ($this->assignmentScopeType !== 'employee' || count($this->assignmentScopeIds) !== 1) {
            return;
        }

        $employee = Employee::where('company_id', $this->companyId)->find((int) $this->assignmentScopeIds[0]);
        if ($employee) {
            $this->inheritanceChain = app(AttendancePolicyAssignmentService::class)
                ->inheritanceChainForEmployee($employee);
        }
    }

    public function saveAssignment(): void
    {
        if (!$this->canManage()) {
            return;
        }

        if ($this->assignmentPolicyId === '') {
            $this->addError('assignment_policy_id', 'Please select a policy.');

            return;
        }

        if ($this->assignmentScopeType !== 'company' && empty($this->assignmentScopeIds)) {
            $this->addError('assignment_scope_ids', 'Please select at least one scope.');

            return;
        }

        try {
            $result = app(AttendancePolicyAssignmentService::class)->assignBulk(
                (int) $this->companyId,
                [
                    'scope_type' => $this->assignmentScopeType,
                    'policy_id' => (int) $this->assignmentPolicyId,
                    'effective_from' => $this->assignmentEffectiveFrom,
                    'effective_to' => $this->assignmentEffectiveTo !== '' ? $this->assignmentEffectiveTo : null,
                ],
                array_map('intval', $this->assignmentScopeIds),
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('assignment_' . $field, $messages[0]);
            }

            return;
        }

        $createdCount = $result['created']->count();
        $failedCount = count($result['failed']);

        if ($createdCount === 0 && $failedCount > 0) {
            $this->addError('assignment_bulk', $result['failed'][0]['message']);

            return;
        }

        $message = $createdCount === 1 ? 'Assignment saved.' : "{$createdCount} assignments saved.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} failed.";
        }

        session()->flash('success', $message);
        $this->assignmentPolicyId = '';
        $this->assignmentScopeIds = [];
        $this->inheritanceChain = [];
        $this->assignmentEffectiveTo = '';
    }

    public function deleteAssignment(int $assignmentId): void
    {
        if (!$this->canManage()) {
            return;
        }

        app(AttendancePolicyAssignmentService::class)->delete((int) $this->companyId, $assignmentId);
        session()->flash('success', 'Assignment removed.');
    }

    // --- Monthly ---

    private function monthlyRowKey(int $employeeId): string
    {
        return 'e' . $employeeId;
    }

    public function updatedMonth(): void
    {
        $this->monthlyData = [];
        $this->resetPage('monthlyPage');
    }

    public function updatedYear(): void
    {
        $this->monthlyData = [];
        $this->resetPage('monthlyPage');
    }

    public function updatedSelectedLocation(): void
    {
        $this->monthlyData = [];
        $this->resetPage('monthlyPage');
    }

    public function updatedSelectedDepartment(): void
    {
        $this->monthlyData = [];
        $this->resetPage('monthlyPage');
    }

    public function updatedSelectedDesignation(): void
    {
        $this->monthlyData = [];
        $this->resetPage('monthlyPage');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function monthlyRowsForSave(): array
    {
        $leaveTypes = LeaveType::where('company_id', (int) $this->companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        foreach ($this->monthlyData as $rowKey => $data) {
            if (!is_array($data)) {
                continue;
            }

            $employeeId = (int) ($data['employee_id'] ?? ltrim((string) $rowKey, 'e'));
            if ($employeeId <= 0) {
                continue;
            }

            $leaves = [];
            foreach ($leaveTypes as $idx => $lt) {
                $leaves[$lt->code] = (float) ($data['leave_' . $idx] ?? $data[strtolower($lt->code)] ?? 0);
            }

            $rows[$employeeId] = [
                'employee_id' => $employeeId,
                'working_days' => $data['working_days'] ?? 26,
                'leaves' => $leaves,
                'esi_leave' => $data['esi_leave'] ?? 0,
                'holiday' => $data['holiday'] ?? 0,
                'tot_dys' => $data['tot_dys'] ?? 0,
            ];
        }

        return $rows;
    }

    public function toggleMonthlyEditMode(): void
    {
        if ($this->monthlyEditMode) {
            $this->monthlyData = [];
        }

        $this->monthlyEditMode = !$this->monthlyEditMode;
    }

    public function saveMonthly(): void
    {
        if (!$this->canManage()) {
            return;
        }

        try {
            app(AttendanceCommandService::class)->saveMonthly(
                (int) $this->companyId,
                $this->month,
                $this->year,
                $this->monthlyRowsForSave(),
            );
            session()->flash('success', 'Monthly attendance saved successfully.');
            $this->monthlyEditMode = false;
            $this->monthlyData = [];
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * @param  list<array<string, mixed>>  $gridRows
     */
    public function saveMonthlyMatrix(array $gridRows): void
    {
        if (!$this->canManage()) {
            return;
        }

        $leaveTypes = LeaveType::where('company_id', (int) $this->companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        try {
            $rows = app(AttendanceMonthlyMatrixService::class)->rowsForSave(
                $leaveTypes,
                $gridRows,
            );

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'monthlyMatrix' => 'No rows to save.',
                ]);
            }

            app(AttendanceCommandService::class)->saveMonthly(
                (int) $this->companyId,
                $this->month,
                $this->year,
                $rows,
            );

            session()->flash('success', 'Monthly attendance saved successfully.');
            $this->monthlyEditMode = false;
            $this->monthlyData = [];
            $this->dispatchBrowserEvent('attendance-monthly-matrix-saved');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    public function importExcel(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $this->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $result = app(AttendanceService::class)->importFromExcel(
                (int) $this->companyId,
                $this->excel_file->getRealPath(),
                $this->month,
                $this->year,
            );

            $this->reset('excel_file');
            $this->monthlyData = [];

            if ($result['failed'] > 0) {
                $firstError = collect($result['errors'])->first();
                $message = 'Import completed with ' . $result['failed'] . ' failure(s).';
                if ($firstError) {
                    $message .= ' ' . $firstError;
                }
                session()->flash('error', $message);
            } else {
                session()->flash('success', 'Monthly attendance imported successfully.');
            }

            $this->dispatchBrowserEvent('attendance-monthly-matrix-saved');
        } catch (\Throwable $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $columns = app(AttendanceService::class)->buildImportTemplateColumns(
            (int) $this->companyId,
        );
        $sampleRow = array_fill(0, count($columns), '');

        return Excel::download(new AttendanceTemplateExport($columns, [$sampleRow]), 'monthly_attendance_template.xlsx');
    }

    // --- Daily ---

    public function updatedDailyMonth(): void
    {
        $this->syncDailyRangeEnd();
        $this->dailyMatrix = [];
    }

    public function updatedDailyYear(): void
    {
        $this->syncDailyRangeEnd();
        $this->dailyMatrix = [];
    }

    private function syncDailyRangeEnd(): void
    {
        $days = Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1)->daysInMonth;
        $this->dailyRangeEnd = min(max($this->dailyRangeEnd, $this->dailyRangeStart), $days);
    }

    public function toggleDailyEditMode(): void
    {
        $this->dailyEditMode = !$this->dailyEditMode;
    }

    public function applyCalendarToDaily(): void
    {
        if (!$this->dailyEditMode) {
            return;
        }

        $dailyService = app(DailyAttendanceService::class);
        $dates = $dailyService->datesInRange(
            $this->dailyMonth,
            $this->dailyYear,
            $this->dailyRangeStart,
            $this->dailyRangeEnd,
        );

        foreach ($this->dailyMatrix as $employeeId => $dateCells) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                continue;
            }

            $defaults = $dailyService->calendarDefaultsForEmployee($employee, $this->dailyMonth, $this->dailyYear, $dates);
            foreach ($dates as $date) {
                if (!empty($defaults[$date]['status']) && empty($this->dailyMatrix[$employeeId][$date]['attendance_status'])) {
                    $this->dailyMatrix[$employeeId][$date]['attendance_status'] = $defaults[$date]['status'];
                    $this->dailyMatrix[$employeeId][$date]['is_auto'] = true;
                }
            }
        }
    }

    public function applyBulkStatusToColumn(string $date): void
    {
        if (!$this->dailyEditMode) {
            return;
        }

        foreach ($this->dailyMatrix as $employeeId => $dates) {
            if (isset($dates[$date])) {
                $this->dailyMatrix[$employeeId][$date]['attendance_status'] = $this->bulkStatus;
            }
        }
    }

    public function applyBulkStatusToEmployee(int $employeeId): void
    {
        if (!$this->dailyEditMode || !isset($this->dailyMatrix[$employeeId])) {
            return;
        }

        foreach ($this->dailyMatrix[$employeeId] as $date => $cell) {
            $this->dailyMatrix[$employeeId][$date]['attendance_status'] = $this->bulkStatus;
        }
    }

    public function saveDaily(): void
    {
        if (!$this->canManage()) {
            return;
        }

        $rows = [];
        foreach ($this->dailyMatrix as $employeeId => $dates) {
            foreach ($dates as $date => $cell) {
                if (empty($cell['attendance_status'])) {
                    continue;
                }
                $rows[] = [
                    'employee_id' => (int) $employeeId,
                    'attendance_date' => $date,
                    'attendance_status' => $cell['attendance_status'],
                    'leave_type_id' => !empty($cell['leave_type_id']) ? (int) $cell['leave_type_id'] : null,
                    'first_half_status' => $cell['first_half_status'] ?? null,
                    'second_half_status' => $cell['second_half_status'] ?? null,
                ];
            }
        }

        try {
            $result = app(AttendanceCommandService::class)->saveDaily(
                (int) $this->companyId,
                $this->dailyMonth,
                $this->dailyYear,
                $rows,
            );
            session()->flash('success', "Saved {$result['saved']} daily records; refreshed {$result['aggregated']} monthly summaries.");
            $this->dailyEditMode = false;
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    private function getMonthlyEmployeesQuery()
    {
        $query = Employee::query()
            ->where('company_id', $this->companyId)
            ->whereNull('dol')
            ->with(['department', 'designation']);

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }
        if ($this->selectedDesignation) {
            $query->where('designation_id', $this->selectedDesignation);
        }
        if ($this->selectedLocation) {
            $query->where('location_id', $this->selectedLocation);
        }

        return $query;
    }

    private function getDailyEmployeesQuery()
    {
        $query = Employee::query()
            ->where('company_id', $this->companyId)
            ->whereNull('dol')
            ->with(['department', 'designation']);

        if ($this->dailyDepartment) {
            $query->where('department_id', $this->dailyDepartment);
        }
        if ($this->dailyDesignation) {
            $query->where('designation_id', $this->dailyDesignation);
        }
        if ($this->dailyLocation) {
            $query->where('location_id', $this->dailyLocation);
        }

        return $query;
    }

    private function hydrateMonthlyData($employees, $leaveTypes): void
    {
        $resolver = app(AttendancePolicyResolver::class);

        foreach ($employees as $employee) {
            $rowKey = $this->monthlyRowKey($employee->id);

            if (isset($this->monthlyData[$rowKey]) && $this->monthlyEditMode) {
                continue;
            }

            $policy = $resolver->resolveForEmployee($employee);
            if ($policy && $policy->attendance_mode === AttendanceMode::DAILY_MARKING) {
                continue;
            }

            $attendance = MonthlyAttendance::where('employee_id', $employee->id)
                ->where('company_id', $this->companyId)
                ->where('month', $this->month)
                ->where('year', $this->year)
                ->with('leaveBreakdown.leaveType')
                ->first();

            $row = [
                'employee_id' => $employee->id,
            ];
            foreach ($leaveTypes as $idx => $lt) {
                $row['leave_' . $idx] = $attendance?->leaveDaysForCode($lt->code) ?? 0;
            }

            $policyResolved = $policy !== null;
            $workingDays = $policyResolved
                ? $resolver->resolveMonthlyWorkingDays($employee, $this->month, $this->year, $policy)
                : (float) ($attendance?->working_days ?? 26);

            $this->monthlyData[$rowKey] = array_merge($row, [
                'working_days' => $workingDays,
                'policy_resolved' => $policyResolved,
                'esi_leave' => $attendance?->esi_la ?? 0,
                'holiday' => $attendance?->holiday_days ?? 0,
                'tot_dys' => $attendance?->total_days ?? 0,
                'policy_mode' => $policy?->attendance_mode->value ?? 'monthly_summary',
            ]);
        }
    }

    private function hydrateDailyMatrix($employees, array $dates, $existingRecords): void
    {
        $indexed = $existingRecords->groupBy('employee_id');

        foreach ($employees as $employee) {
            $policy = app(AttendancePolicyResolver::class)->resolveForEmployee($employee);
            if (!$policy || $policy->attendance_mode !== AttendanceMode::DAILY_MARKING) {
                continue;
            }

            if (!isset($this->dailyMatrix[$employee->id]) || !$this->dailyEditMode) {
                $this->dailyMatrix[$employee->id] = [];
            }

            $empRecords = $indexed->get($employee->id, collect())->keyBy(fn($r) => $r->attendance_date->toDateString());

            foreach ($dates as $date) {
                if ($this->dailyEditMode && isset($this->dailyMatrix[$employee->id][$date])) {
                    continue;
                }

                $record = $empRecords->get($date);
                $defaults = app(DailyAttendanceService::class)->calendarDefaultsForEmployee(
                    $employee,
                    $this->dailyMonth,
                    $this->dailyYear,
                    [$date],
                );

                $status = $record?->attendance_status?->value
                    ?? ($defaults[$date]['status'] ?? '');

                $this->dailyMatrix[$employee->id][$date] = [
                    'attendance_status' => $status,
                    'leave_type_id' => $record?->leave_type_id ? (string) $record->leave_type_id : '',
                    'first_half_status' => $record?->first_half_status?->value ?? 'present',
                    'second_half_status' => $record?->second_half_status?->value ?? 'leave',
                    'is_auto' => empty($record) && !empty($defaults[$date]['is_auto']),
                ];
            }
        }
    }

    public function render()
    {
        $companyId = (int) $this->companyId;
        $policies = app(AttendancePolicyService::class)->listForCompany($companyId);
        $assignments = app(AttendancePolicyAssignmentService::class)->listForCompany($companyId);
        $leaveTypes = LeaveType::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();
        $locations = Location::where('company_id', $companyId)->orderBy('location_name')->get();
        $departments = Department::where('company_id', $companyId)->orderBy('department_name')->get();
        $designations = Designation::where('company_id', $companyId)->orderBy('designation_name')->get();
        $allEmployees = Employee::where('company_id', $companyId)->orderBy('employee_name')->get();

        $assignmentScopeNames = [];
        foreach ($assignments as $assignment) {
            $assignmentScopeNames[$assignment->id] = match ($assignment->scope_type->value) {
                'company' => 'Company',
                'location' => $locations->firstWhere('id', $assignment->scope_id)?->location_name ?? 'Location #' . $assignment->scope_id,
                'department' => $departments->firstWhere('id', $assignment->scope_id)?->department_name ?? 'Department #' . $assignment->scope_id,
                'designation' => $designations->firstWhere('id', $assignment->scope_id)?->designation_name ?? 'Designation #' . $assignment->scope_id,
                'employee' => $allEmployees->firstWhere('id', $assignment->scope_id)?->employee_name ?? 'Employee #' . $assignment->scope_id,
                default => (string) $assignment->scope_id,
            };
        }

        $monthlyMatrix = [
            'rowData' => [],
            'leaveTypes' => [],
            'editable' => $this->monthlyEditMode && $this->canManage(),
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
            ],
        ];

        if ($this->activeTab === 'monthly') {
            $monthlyEmployees = $this->getMonthlyEmployeesQuery()->get();
            $this->hydrateMonthlyData($monthlyEmployees, $leaveTypes);
            $monthlyMatrix = app(AttendanceMonthlyMatrixService::class)->buildMatrix(
                $this->month,
                $this->year,
                $monthlyEmployees,
                $leaveTypes,
                $this->monthlyData,
                $this->monthlyEditMode && $this->canManage(),
            );
        }

        $dailyDates = app(DailyAttendanceService::class)->datesInRange(
            $this->dailyMonth,
            $this->dailyYear,
            $this->dailyRangeStart,
            $this->dailyRangeEnd,
        );

        $dailyEmployees = $this->activeTab === 'daily'
            ? $this->getDailyEmployeesQuery()->paginate(8, ['*'], 'dailyPage')
            : collect();

        $existingDaily = collect();
        if ($this->activeTab === 'daily' && $dailyEmployees instanceof \Illuminate\Pagination\LengthAwarePaginator && $dailyDates !== []) {
            $employeeIds = $dailyEmployees->pluck('id')->all();
            $existingDaily = app(DailyAttendanceService::class)->recordsForPeriod(
                $companyId,
                $employeeIds,
                $dailyDates[0],
                $dailyDates[count($dailyDates) - 1],
            );
            $this->hydrateDailyMatrix($dailyEmployees, $dailyDates, $existingDaily);
        }

        $statusOptions = collect(AttendanceStatus::cases())->map(fn($s) => [
            'value' => $s->value,
            'label' => ucfirst(str_replace('_', ' ', $s->value)),
        ]);

        $holidays = $this->activeTab === 'holidays'
            ? app(HolidayCalendarService::class)->listForCompany($companyId, $this->holidayFilterMonth, $this->holidayFilterYear)
            : collect();

        $adminLeaveTypes = $this->activeTab === 'leave_types'
            ? app(LeaveTypeService::class)->listForCompany($companyId)
            : collect();

        $exceptionRules = $this->activeTab === 'exception_rules'
            ? app(LeaveExceptionRuleService::class)->listForCompany($companyId)
            : collect();

        $company = Company::find($companyId);
        $showDailyMarkingTab = app(AttendanceSettingsAdapter::class)->showDailyMarking($companyId);

        return view('livewire.attendance-hub', [
            'companyName' => $company?->company_name ?? 'Company',
            'showDailyMarkingTab' => $showDailyMarkingTab,
            'policies' => $policies,
            'assignments' => $assignments,
            'assignmentScopeNames' => $assignmentScopeNames,
            'leaveTypes' => $leaveTypes,
            'locations' => $locations,
            'departments' => $departments,
            'designations' => $designations,
            'allEmployees' => $allEmployees,
            'dailyEmployees' => $dailyEmployees,
            'dailyDates' => $dailyDates,
            'statusOptions' => $statusOptions,
            'holidays' => $holidays,
            'adminLeaveTypes' => $adminLeaveTypes,
            'exceptionRules' => $exceptionRules,
            'monthlyMatrix' => $monthlyMatrix,
        ]);
    }
}
