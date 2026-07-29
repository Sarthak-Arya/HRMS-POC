<?php

namespace Tests\Feature;

use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\Attendance\AttendancePolicyAssignmentService;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Attendance\HolidayCalendarService;
use App\Services\Attendance\WeeklyOffPatternService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceHolidayCalendarTest extends TestCase
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
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);
    }

    public function test_create_and_list_holidays(): void
    {
        $service = app(HolidayCalendarService::class);

        $holiday = $service->create($this->company->id, [
            'holiday_date' => '2026-08-15',
            'name' => 'Independence Day',
            'source_type' => 'public',
            'is_paid' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('company_holidays', [
            'company_id' => $this->company->id,
            'name' => 'Independence Day',
            'source_type' => 'public',
        ]);

        $listed = $service->listForCompany($this->company->id, 8, 2026);
        $this->assertCount(1, $listed);
        $this->assertSame($holiday->id, $listed->first()->id);
    }

    public function test_weekly_off_pattern_alternate_saturday(): void
    {
        $policy = app(AttendancePolicyService::class)->create($this->company->id, [
            'policy_name' => 'Alt Sat Policy',
            'attendance_mode' => AttendanceMode::DAILY_MARKING->value,
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'alternate_saturday',
            'alternate_saturday_weeks' => [2, 4],
            'grace_minutes' => 0,
            'min_half_day_minutes' => 240,
            'min_full_day_minutes' => 480,
            'allow_overtime' => false,
            'allow_half_day' => true,
            'allow_negative_leave_balance' => false,
            'is_active' => true,
        ], []);

        $dates = app(WeeklyOffPatternService::class)->resolveWeeklyOffDates($policy, 6, 2026);

        $this->assertContains('2026-06-07', $dates);
        $this->assertContains('2026-06-14', $dates);
        $this->assertNotContains('2026-06-06', $dates);
    }
}
