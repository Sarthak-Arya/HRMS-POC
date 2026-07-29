<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAssignmentResolutionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private AttendancePolicy $companyPolicy;
    private AttendancePolicy $designationPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Test Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
        $this->companyPolicy = AttendancePolicy::where('company_id', $this->company->id)->first();

        $department = Department::create([
            'company_id' => $this->company->id,
            'department_name' => 'Engineering',
        ]);

        $designation = Designation::create([
            'company_id' => $this->company->id,
            'designation_name' => 'Senior Dev',
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

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'E001',
            'employee_name' => 'Test Employee',
            'gender' => 'M',
            'father_name' => 'Father',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $leaveTypes = app(AttendanceSetupService::class)->seedLeaveTypes($this->company->id);
        $this->designationPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Designation Daily',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'grace_minutes' => 0,
            'min_half_day_minutes' => 240,
            'min_full_day_minutes' => 480,
            'allow_overtime' => false,
            'allow_half_day' => true,
            'allow_negative_leave_balance' => false,
            'is_active' => true,
        ], []);

        $assignmentService = app(AttendancePolicyAssignmentService::class);
        $assignmentService->assign($this->company->id, [
            'scope_type' => 'designation',
            'scope_id' => $designation->id,
            'policy_id' => $this->designationPolicy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_designation_policy_overrides_company_policy(): void
    {
        $resolved = app(AttendancePolicyResolver::class)->resolveForEmployee(
            $this->employee,
            Carbon::parse('2026-06-15'),
        );

        $this->assertNotNull($resolved);
        $this->assertSame('Designation Daily', $resolved->policy_name);
        $this->assertSame(AttendanceMode::DAILY_MARKING, $resolved->attendance_mode);
    }

    public function test_inheritance_chain_shows_designation_and_company(): void
    {
        $chain = app(AttendancePolicyResolver::class)->policyInheritanceChain(
            $this->employee,
            Carbon::parse('2026-06-15'),
        );

        $this->assertSame('Designation Daily', $chain['designation']['policy_name']);
        $this->assertSame('Company Policy', $chain['company']['policy_name']);
    }

    public function test_assignment_overlap_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'designation',
            'scope_id' => $this->employee->designation_id,
            'policy_id' => $this->designationPolicy->id,
            'effective_from' => '2026-06-01',
        ]);
    }
}
