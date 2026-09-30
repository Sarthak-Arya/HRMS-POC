<?php

namespace Tests\Feature;

use App\Enums\Leave\LeaveRequestStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\LeaveBalanceResolver;
use App\Services\Ess\EmployeePortalAccountService;
use App\Services\Ess\LeaveRequestService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private Employee $managerEmployee;

    private User $employeeUser;

    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $owner = User::factory()->hrManager()->create();
        $this->company = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'ESS Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        $policy = app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);

        $department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Ops',
        ]);
        $designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Staff',
        ]);
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

        $this->managerUser = User::factory()->manager()->forCompany($this->company)->create([
            'email' => 'manager@ess.test',
        ]);
        $this->managerEmployee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->managerUser->id,
            'employee_code' => 'MGR1',
            'employee_name' => 'Team Manager',
            'work_email' => 'manager@ess.test',
            'gender' => 'M',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
            'doj' => now()->subYears(2)->toDateString(),
        ]);

        $this->employeeUser = User::factory()->employee()->forCompany($this->company)->create([
            'email' => 'employee@ess.test',
        ]);
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'manager_id' => $this->managerEmployee->id,
            'employee_code' => 'EMP1',
            'employee_name' => 'Portal Employee',
            'work_email' => 'employee@ess.test',
            'gender' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
            'doj' => now()->subYear()->toDateString(),
        ]);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $policy->id,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);

        app(LeaveBalanceResolver::class)->initializeBalancesForEmployee(
            $this->employee,
            $policy,
            (int) now()->year,
        );
    }

    public function test_employee_can_open_portal_home(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('ess.home', ['company_id' => $this->company->id]))
            ->assertOk()
            ->assertSee('Portal Employee');
    }

    public function test_employee_without_link_is_forbidden(): void
    {
        $orphan = User::factory()->employee()->forCompany($this->company)->create();

        $this->actingAs($orphan)
            ->get(route('ess.home', ['company_id' => $this->company->id]))
            ->assertForbidden();
    }

    public function test_leave_submit_and_manager_approve(): void
    {
        $leaveType = LeaveType::query()
            ->where('company_id', $this->company->id)
            ->where('code', 'CL')
            ->firstOrFail();

        // Pick a weekday ahead to avoid weekend/holiday edge cases in calendar.
        $start = now()->next('Monday');
        if ($start->isPast()) {
            $start = $start->addWeek();
        }

        $service = app(LeaveRequestService::class);
        $request = $service->submit($this->employee, [
            'leave_type_id' => $leaveType->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'day_portion' => 'full',
            'reason' => 'Personal',
        ]);

        $this->assertTrue($request->isPending());
        $this->assertEquals(1.0, (float) $request->days);

        $pending = $service->pendingForApprover($this->managerUser, $this->company->id);
        $this->assertTrue($pending->contains('id', $request->id));

        $approved = $service->approve($request, $this->managerUser, 'OK');
        $this->assertSame(LeaveRequestStatus::Approved, $approved->status);
    }

    public function test_portal_invite_creates_linked_user(): void
    {
        $fresh = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP2',
            'employee_name' => 'Invitee',
            'gender' => 'M',
            'department_id' => $this->employee->department_id,
            'designation_id' => $this->employee->designation_id,
            'location_id' => $this->employee->location_id,
            'doj' => now()->toDateString(),
        ]);

        $hr = User::factory()->hrManager()->forCompany($this->company)->create();
        $this->actingAs($hr);

        $result = app(EmployeePortalAccountService::class)->invite(
            $fresh,
            'invitee@ess.test',
            UserRole::Employee,
            'SecretPass1!',
        );

        $this->assertTrue($result['created']);
        $this->assertNotNull($fresh->fresh()->user_id);
        $this->assertTrue($result['user']->hasPermission(Permission::EssAccess));
    }

    public function test_employee_lands_on_ess_home_after_login_routing(): void
    {
        $route = app(\App\Services\Auth\AuthLandingService::class)->homeRoute($this->employeeUser);
        $this->assertStringContainsString('/me', $route);
    }
}
