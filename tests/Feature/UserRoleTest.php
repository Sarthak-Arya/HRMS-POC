<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_access_any_company(): void
    {
        $admin = User::factory()->admin()->create();
        $payrollManager = User::factory()->create();
        $otherManager = User::factory()->create();

        $company = Company::factory()->create([
            'company_handled_by' => $otherManager->id,
        ]);

        $this->assertTrue($admin->canAccessCompany($company));
        $this->assertFalse($payrollManager->canAccessCompany($company));
        $this->assertTrue($otherManager->canAccessCompany($company));
    }

    public function test_payroll_manager_can_access_assigned_companies_only(): void
    {
        $payrollManager = User::factory()->create();
        $otherManager = User::factory()->create();

        $assignedCompany = Company::factory()->create([
            'company_handled_by' => $payrollManager->id,
        ]);
        $otherCompany = Company::factory()->create([
            'company_handled_by' => $otherManager->id,
        ]);

        $this->assertTrue($payrollManager->canAccessCompany($assignedCompany));
        $this->assertFalse($payrollManager->canAccessCompany($otherCompany));
    }

    public function test_admin_sees_all_companies_on_view_companies_page(): void
    {
        $admin = User::factory()->admin()->create();
        $payrollManager = User::factory()->create();

        $adminCompany = Company::factory()->create([
            'company_name' => 'Admin Visible Co',
            'company_handled_by' => $admin->id,
        ]);
        $payrollCompany = Company::factory()->create([
            'company_name' => 'Payroll Visible Co',
            'company_handled_by' => $payrollManager->id,
        ]);

        $response = $this->actingAs($admin)->get(route('view-companies'));

        $response->assertOk();
        $response->assertSee($adminCompany->company_name);
        $response->assertSee($payrollCompany->company_name);
    }

    public function test_seeded_users_have_expected_roles(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@softui.com']);
        $payrollManager = User::factory()->payrollManager()->create(['email' => 'payroll@softui.com']);

        $this->assertTrue($admin->hasRole(UserRole::Admin));
        $this->assertTrue($payrollManager->hasRole(UserRole::PayrollManager));
    }
}
