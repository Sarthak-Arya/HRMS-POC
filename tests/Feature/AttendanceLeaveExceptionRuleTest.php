<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendanceLeaveExceptionRule;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendanceCommandService;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\LeaveExceptionRuleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceLeaveExceptionRuleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private int $lwpTypeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Ex Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
        $this->lwpTypeId = (int) LeaveType::where('company_id', $this->company->id)->where('code', 'LWP')->value('id');

        $department = Department::create(['company_id' => $this->company->id, 'department_name' => 'Ops']);
        $designation = Designation::create(['company_id' => $this->company->id, 'designation_name' => 'Staff']);
        $location = Location::create([
            'company_id' => $this->company->id,
            'location_name' => 'HQ',
            'location_code' => 'HQ',
            'location_address' => '',
            'location_city' => '',
            'location_state' => '',
            'location_pincode' => '',
            'location_country' => '',
            'location_phone' => '',
            'location_email' => '',
        ]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'E100',
            'employee_name' => 'Exception Emp',
            'gender' => 'M',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Monthly Ex',
            'attendance_mode' => AttendanceMode::MONTHLY_SUMMARY->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'is_active' => true,
        ], []);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $policy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_lwp_monthly_cap_blocks_save(): void
    {
        app(LeaveExceptionRuleService::class)->create($this->company->id, [
            'leave_type_id' => $this->lwpTypeId,
            'max_days_per_month' => 2,
            'on_exceed' => 'block',
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceCommandService::class)->saveMonthly($this->company->id, 6, 2026, [
            $this->employee->id => [
                'leaves' => ['LWP' => 3],
                'working_days' => 26,
                'tot_dys' => 26,
            ],
        ]);
    }

    public function test_exception_rule_crud(): void
    {
        $service = app(LeaveExceptionRuleService::class);
        $rule = $service->create($this->company->id, [
            'leave_type_id' => $this->lwpTypeId,
            'max_days_per_month' => 1,
            'on_exceed' => 'warn',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(AttendanceLeaveExceptionRule::class, $rule);
        $service->update($this->company->id, $rule->id, ['on_exceed' => 'block', 'is_active' => true]);
        $this->assertDatabaseHas('attendance_leave_exception_rules', ['id' => $rule->id, 'on_exceed' => 'block']);
    }
}
