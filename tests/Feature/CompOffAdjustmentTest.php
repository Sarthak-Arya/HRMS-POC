<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\CompOffService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompOffAdjustmentTest extends TestCase
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

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);

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
            'employee_code' => 'E012',
            'employee_name' => 'CompOff Employee',
            'gender' => 'M',
            'father_name' => 'Father',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Comp Off Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'comp_off_enabled' => true,
            'grace_minutes' => 0,
            'min_half_day_minutes' => 240,
            'min_full_day_minutes' => 480,
            'allow_overtime' => false,
            'allow_half_day' => true,
            'allow_negative_leave_balance' => false,
            'is_active' => true,
        ], []);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $policy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_comp_off_credited_for_work_on_sunday(): void
    {
        $policy = app(AttendancePolicyService::class)->listForCompany($this->company->id)
            ->firstWhere('policy_name', 'Comp Off Policy');

        $record = EmployeeAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'attendance_date' => '2026-06-07',
            'attendance_status' => AttendanceStatus::PRESENT->value,
            'source' => 'manual',
        ]);

        $service = app(CompOffService::class);
        $credited = $service->processAccruals($this->employee, $policy, [$record]);

        $this->assertSame(1, $credited);
        $this->assertEquals(1.0, $service->availableBalance($this->employee));
        $this->assertDatabaseHas('employee_comp_off_ledger', [
            'employee_id' => $this->employee->id,
            'entry_type' => 'earned',
            'days' => 1,
        ]);
    }
}
