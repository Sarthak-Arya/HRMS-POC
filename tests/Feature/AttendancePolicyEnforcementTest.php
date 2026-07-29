<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Attendance\AttendanceCommandService;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendancePolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $dailyEmployee;
    private Employee $monthlyEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Enf Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);

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

        $this->dailyEmployee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'ED01',
            'employee_name' => 'Daily',
            'gender' => 'M',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $this->monthlyEmployee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EM01',
            'employee_name' => 'Monthly',
            'gender' => 'F',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $dailyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Daily Enf',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'allow_half_day' => false,
            'is_active' => true,
        ], []);

        $monthlyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Monthly Enf',
            'attendance_mode' => AttendanceMode::MONTHLY_SUMMARY->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'is_active' => true,
        ], []);

        $assignment = app(AttendancePolicyAssignmentService::class);
        $assignment->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->dailyEmployee->id,
            'policy_id' => $dailyPolicy->id,
            'effective_from' => '2026-01-01',
        ]);
        $assignment->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->monthlyEmployee->id,
            'policy_id' => $monthlyPolicy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_mode_mismatch_blocks_monthly_save_for_daily_employee(): void
    {
        $this->expectException(ValidationException::class);
        app(AttendanceCommandService::class)->saveMonthly($this->company->id, 6, 2026, [
            $this->dailyEmployee->id => ['leaves' => ['CL' => 1]],
        ]);
    }

    public function test_half_day_blocked_when_policy_disallows(): void
    {
        $this->expectException(ValidationException::class);
        app(AttendanceCommandService::class)->saveDaily($this->company->id, 6, 2026, [
            ['employee_id' => $this->dailyEmployee->id, 'attendance_date' => '2026-06-02', 'attendance_status' => 'half_day'],
        ]);
    }

    public function test_locked_summary_blocks_daily_save(): void
    {
        MonthlyAttendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->dailyEmployee->id,
            'month' => 6,
            'year' => 2026,
            'working_days' => 26,
            'total_days' => 26,
            'locked_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceCommandService::class)->saveDaily($this->company->id, 6, 2026, [
            ['employee_id' => $this->dailyEmployee->id, 'attendance_date' => '2026-06-02', 'attendance_status' => 'present'],
        ]);
    }
}
