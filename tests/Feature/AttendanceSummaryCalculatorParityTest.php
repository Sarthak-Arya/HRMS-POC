<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
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
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSummaryCalculatorParityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private int $clId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Parity Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
        $this->clId = (int) LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->value('id');

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
            'employee_code' => 'P001',
            'employee_name' => 'Parity Emp',
            'gender' => 'M',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $dailyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Daily Parity',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'is_active' => true,
        ], []);

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $dailyPolicy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_daily_present_days_match_worked_days_in_summary(): void
    {
        $command = app(AttendanceCommandService::class);
        $command->saveDaily($this->company->id, 6, 2026, [
            ['employee_id' => $this->employee->id, 'attendance_date' => '2026-06-02', 'attendance_status' => 'present'],
            ['employee_id' => $this->employee->id, 'attendance_date' => '2026-06-03', 'attendance_status' => 'present'],
            ['employee_id' => $this->employee->id, 'attendance_date' => '2026-06-04', 'attendance_status' => 'leave', 'leave_type_id' => $this->clId],
        ]);

        $summary = \App\Models\MonthlyAttendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($summary);
        $this->assertEquals(2.0, (float) $summary->present_days);
        $this->assertEquals(2.0, (float) $summary->worked_days);
        $this->assertEquals(1.0, (float) $summary->casual_leave);
    }
}
