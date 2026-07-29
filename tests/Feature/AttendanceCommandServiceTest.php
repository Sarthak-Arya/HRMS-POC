<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceLeaveExceptionRule;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Attendance\AttendanceCommandService;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\MonthLockAndReconciliationService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceCommandServiceTest extends TestCase
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
            'company_name' => 'Cmd Co',
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
            'employee_code' => 'D001',
            'employee_name' => 'Daily Emp',
            'gender' => 'M',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $this->monthlyEmployee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'M001',
            'employee_name' => 'Monthly Emp',
            'gender' => 'F',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $dailyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Daily Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'is_active' => true,
        ], []);

        $monthlyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Monthly Policy',
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

    public function test_save_daily_through_command_creates_summary_and_audit(): void
    {
        $command = app(AttendanceCommandService::class);
        $result = $command->saveDaily($this->company->id, 6, 2026, [
            ['employee_id' => $this->dailyEmployee->id, 'attendance_date' => '2026-06-02', 'attendance_status' => 'present'],
            ['employee_id' => $this->dailyEmployee->id, 'attendance_date' => '2026-06-03', 'attendance_status' => 'present'],
        ]);

        $this->assertGreaterThan(0, $result['saved']);
        $this->assertDatabaseHas('employee_attendance_summaries', [
            'employee_id' => $this->dailyEmployee->id,
            'month' => 6,
            'year' => 2026,
        ]);
        $this->assertDatabaseHas('attendance_audit_logs', [
            'company_id' => $this->company->id,
            'action' => 'daily_saved',
        ]);
    }

    public function test_save_monthly_through_command(): void
    {
        $command = app(AttendanceCommandService::class);
        $saved = $command->saveMonthly($this->company->id, 6, 2026, [
            $this->monthlyEmployee->id => [
                'leaves' => ['CL' => 1, 'EL' => 0, 'SL' => 0],
                'working_days' => 26,
                'tot_dys' => 26,
            ],
        ]);

        $this->assertSame(1, $saved);
        $this->assertDatabaseHas('attendance_audit_logs', ['action' => 'monthly_created']);
    }

    public function test_locked_summary_blocks_monthly_save(): void
    {
        MonthlyAttendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->monthlyEmployee->id,
            'month' => 6,
            'year' => 2026,
            'working_days' => 26,
            'total_days' => 26,
            'locked_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceCommandService::class)->saveMonthly($this->company->id, 6, 2026, [
            $this->monthlyEmployee->id => ['leaves' => ['CL' => 1]],
        ]);
    }
}
