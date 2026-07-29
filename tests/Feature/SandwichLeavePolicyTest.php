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
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Attendance\SandwichLeaveService;
use App\Models\EmployeeAttendance;
use App\Enums\Attendance\AttendanceStatus;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SandwichLeavePolicyTest extends TestCase
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
            'employee_code' => 'E011',
            'employee_name' => 'Sandwich Employee',
            'gender' => 'M',
            'father_name' => 'Father',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Sandwich Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'sandwich_leave_enabled' => true,
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

    public function test_sandwich_expands_leave_across_week_off(): void
    {
        $clId = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->value('id');

        app(DailyAttendanceService::class)->bulkUpsert($this->company->id, 6, 2026, [
            ['employee_id' => $this->employee->id, 'attendance_date' => '2026-06-06', 'attendance_status' => 'leave', 'leave_type_id' => $clId],
            ['employee_id' => $this->employee->id, 'attendance_date' => '2026-06-08', 'attendance_status' => 'leave', 'leave_type_id' => $clId],
        ]);

        $records = EmployeeAttendance::where('employee_id', $this->employee->id)->get();
        $policy = app(AttendancePolicyService::class)->listForCompany($this->company->id)
            ->firstWhere('policy_name', 'Sandwich Policy');

        $expanded = app(SandwichLeaveService::class)->applySandwichRules($records, $policy, 6, 2026, $this->employee);

        $sandwichedSunday = $expanded->first(fn ($r) => $r->attendance_date->toDateString() === '2026-06-07');
        $this->assertNotNull($sandwichedSunday);
        $this->assertSame(AttendanceStatus::LEAVE, $sandwichedSunday->attendance_status);
    }
}
