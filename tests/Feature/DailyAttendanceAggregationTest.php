<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\DailyAttendanceService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceAggregationTest extends TestCase
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
            'employee_code' => 'E002',
            'employee_name' => 'Daily Employee',
            'gender' => 'F',
            'father_name' => 'Father',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
        ]);

        $dailyPolicy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Daily Only',
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

        app(AttendancePolicyAssignmentService::class)->assign($this->company->id, [
            'scope_type' => 'employee',
            'scope_id' => $this->employee->id,
            'policy_id' => $dailyPolicy->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_daily_marking_aggregates_to_monthly_summary(): void
    {
        $clLeaveTypeId = \App\Models\LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->value('id');

        $rows = [
            [
                'employee_id' => $this->employee->id,
                'attendance_date' => '2026-06-02',
                'attendance_status' => 'present',
            ],
            [
                'employee_id' => $this->employee->id,
                'attendance_date' => '2026-06-03',
                'attendance_status' => 'present',
            ],
            [
                'employee_id' => $this->employee->id,
                'attendance_date' => '2026-06-04',
                'attendance_status' => 'leave',
                'leave_type_id' => $clLeaveTypeId,
            ],
        ];

        $result = app(DailyAttendanceService::class)->bulkUpsert($this->company->id, 6, 2026, $rows);

        $this->assertSame(3, $result['saved']);
        $this->assertSame(1, $result['aggregated']);

        $summary = MonthlyAttendance::where('employee_id', $this->employee->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame('daily_aggregated', $summary->entry_source->value);
        $this->assertEquals(2.0, (float) $summary->present_days);
        $this->assertEquals(1.0, (float) $summary->casual_leave);
        $this->assertEquals(26.0, (float) $summary->working_days);
        $this->assertGreaterThan(0, (float) $summary->worked_days);
    }
}
