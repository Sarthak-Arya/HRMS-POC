<?php

namespace Tests\Feature;

use App\Models\AttendanceAuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Attendance\AttendanceAuditService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceSetupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_create_writes_audit_log(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->hrManager()->create();
        $this->actingAs($user);

        $company = Company::factory()->ownedBy($user)->create([
            'company_name' => 'Audit Co',
            'company_address' => 'Addr',
            'is_esi' => false,
            'is_pf' => false,
        ]);

        app(AttendanceSetupService::class)->seedCompanyDefaults($company->id);

        app(AttendancePolicyService::class)->create($company->id, [
            'policy_name' => 'Audited Policy',
            'attendance_mode' => 'monthly_summary',
            'working_days_basis' => 'fixed_26',
            'weekly_off_rule' => 'sunday',
            'is_active' => true,
        ], []);

        $this->assertDatabaseHas('attendance_audit_logs', [
            'company_id' => $company->id,
            'action' => 'policy_created',
            'entity_type' => 'attendance_policy',
        ]);
    }

    public function test_audit_service_persists_entry(): void
    {
        $company = Company::factory()->create();
        $log = app(AttendanceAuditService::class)->log(
            $company->id,
            'test_entity',
            1,
            'test_action',
            ['a' => 1],
            ['a' => 2],
        );

        $this->assertInstanceOf(AttendanceAuditLog::class, $log);
        $this->assertSame('test_action', $log->action);
    }
}
