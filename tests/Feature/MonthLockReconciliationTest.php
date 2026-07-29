<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\MonthLockAndReconciliationService;
use App\Services\Payroll\PayrollReadinessService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthLockReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private PayrollRun $run;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Lock Co',
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

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'L001',
            'employee_name' => 'Lock Emp',
            'gender' => 'M',
            'father_name' => 'F',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Monthly Lock',
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

        MonthlyAttendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'month' => 6,
            'year' => 2026,
            'working_days' => 26,
            'total_days' => 26,
        ]);

        $this->run = PayrollRun::create([
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'status' => 'DRAFT',
        ]);
    }

    public function test_lock_month_for_employee(): void
    {
        app(MonthLockAndReconciliationService::class)->lockMonthForEmployee($this->employee, 6, 2026);

        $summary = MonthlyAttendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($summary->locked_at);
        $this->assertDatabaseHas('attendance_audit_logs', ['action' => 'summary_locked']);
    }

    public function test_payroll_readiness_flags_unlocked_summaries(): void
    {
        $readiness = app(PayrollReadinessService::class)->assess($this->run);
        $this->assertFalse($readiness['is_ready']);
        $this->assertTrue($readiness['unlocked_summaries']->isNotEmpty());
    }
}
