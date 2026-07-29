<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\LeaveBalanceResolver;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalancePriorityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

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

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'E010',
            'employee_name' => 'Balance Employee',
            'gender' => 'M',
            'father_name' => 'Father',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $policy->id,
            'effective_from' => '2026-01-01',
        ]);

        app(LeaveBalanceResolver::class)->initializeBalancesForEmployee($this->employee, $policy, 2026);
    }

    public function test_leave_deducted_in_policy_priority_order(): void
    {
        $policy = app(AttendancePolicyService::class)->listForCompany($this->company->id)->first();
        $resolver = app(LeaveBalanceResolver::class);

        $consumed = $resolver->deductLeaveDays($this->employee, $policy, 3.0, 2026);

        $this->assertEquals(3.0, array_sum($consumed));
        $this->assertArrayHasKey('CL', $consumed);

        $clType = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->first();
        $balance = \App\Models\EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $clType->id)
            ->first();

        $this->assertEquals(3.0, (float) $balance->consumed);
    }
}
