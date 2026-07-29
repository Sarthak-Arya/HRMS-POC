<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendancePolicy;
use App\Models\AttendancePolicyAssignment;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\DailyAttendanceService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePolicyManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Test Co',
            'company_address' => '123 Test St',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
    }

    public function test_create_policy_with_leave_quotas(): void
    {
        $leaveTypes = app(AttendanceSetupService::class)->seedLeaveTypes($this->company->id);

        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Daily HQ Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'paid_holidays' => true,
            'paid_weekly_offs' => true,
            'sandwich_leave_enabled' => false,
            'comp_off_enabled' => false,
            'leave_balance_priority_mode' => 'policy_order',
            'grace_minutes' => 10,
            'min_half_day_minutes' => 240,
            'min_full_day_minutes' => 480,
            'allow_overtime' => false,
            'allow_half_day' => true,
            'allow_negative_leave_balance' => false,
            'is_active' => true,
        ], [
            ['leave_type_id' => $leaveTypes['CL'], 'annual_quota' => 10, 'encashable' => false],
        ]);

        $this->assertDatabaseHas('attendance_policies', [
            'company_id' => $this->company->id,
            'policy_name' => 'Daily HQ Policy',
            'attendance_mode' => 'daily_marking',
        ]);
        $this->assertCount(1, $policy->policyLeaveTypes);
    }

    public function test_deactivate_policy(): void
    {
        $policy = AttendancePolicy::where('company_id', $this->company->id)->first();
        app(AttendancePolicyService::class)->deactivate($this->company->id, $policy->id);

        $this->assertFalse($policy->fresh()->is_active);
    }

    public function test_create_policy_with_alternate_saturday_and_leave_rules(): void
    {
        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Advanced Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'calendar_days',
            'weekly_off_rule' => 'alternate_saturday',
            'alternate_saturday_weeks' => [2, 4],
            'paid_holidays' => true,
            'paid_weekly_offs' => false,
            'auto_adjust_approved_leave' => true,
            'sandwich_leave_enabled' => true,
            'comp_off_enabled' => true,
            'leave_balance_priority_mode' => 'strict_lwp_fallback',
            'grace_minutes' => 0,
            'min_half_day_minutes' => 240,
            'min_full_day_minutes' => 480,
            'allow_overtime' => false,
            'allow_half_day' => true,
            'allow_negative_leave_balance' => false,
            'is_active' => true,
        ], []);

        $this->assertDatabaseHas('attendance_policies', [
            'policy_name' => 'Advanced Policy',
            'weekly_off_rule' => 'alternate_saturday',
            'sandwich_leave_enabled' => true,
            'comp_off_enabled' => true,
        ]);
    }
}
