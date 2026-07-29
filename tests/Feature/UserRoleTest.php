<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\B2bFirm;
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
        $outsider = User::factory()->create();
        $owner = User::factory()->create();

        $company = Company::factory()->ownedBy($owner)->create();

        $this->assertTrue($admin->canAccessCompany($company));
        $this->assertFalse($outsider->canAccessCompany($company));
        $this->assertTrue($owner->fresh()->canAccessCompany($company));
    }

    public function test_b2c_user_can_access_assigned_company_only(): void
    {
        $user = User::factory()->payrollManager()->create();
        $other = User::factory()->create();

        $assignedCompany = Company::factory()->ownedBy($user)->create();
        $otherCompany = Company::factory()->ownedBy($other)->create();

        $this->assertTrue($user->fresh()->canAccessCompany($assignedCompany));
        $this->assertFalse($user->fresh()->canAccessCompany($otherCompany));
    }

    public function test_b2b_users_can_access_all_firm_companies(): void
    {
        $firm = B2bFirm::factory()->create();
        $admin = User::factory()->b2bAdmin()->forB2bFirm($firm)->create();
        $staff = User::factory()->b2bStaff()->forB2bFirm($firm)->create();
        $outsider = User::factory()->b2bStaff()->forB2bFirm()->create();

        $companyA = Company::factory()->forFirm($firm)->create();
        $companyB = Company::factory()->forFirm($firm)->create();
        $otherFirmCompany = Company::factory()->forFirm()->create();

        $this->assertTrue($admin->canAccessCompany($companyA));
        $this->assertTrue($admin->canAccessCompany($companyB));
        $this->assertTrue($staff->canAccessCompany($companyA));
        $this->assertFalse($admin->canAccessCompany($otherFirmCompany));
        $this->assertFalse($outsider->canAccessCompany($companyA));
    }

    public function test_b2c_client_and_b2b_firm_can_both_access_same_company(): void
    {
        $firm = B2bFirm::factory()->create();
        $firmAdmin = User::factory()->b2bAdmin()->forB2bFirm($firm)->create();
        $clientOwner = User::factory()->companyAdmin()->create();

        $company = Company::factory()->forFirm($firm)->create();
        $clientOwner->forceFill([
            'company_id' => $company->id,
            'b2b_firm_id' => null,
        ])->save();

        $this->assertTrue($firmAdmin->canAccessCompany($company));
        $this->assertTrue($clientOwner->fresh()->canAccessCompany($company));
    }

    public function test_admin_sees_all_companies_on_view_companies_page(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $adminCompany = Company::factory()->create([
            'company_name' => 'Admin Visible Co',
        ]);
        $ownerCompany = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'Owner Visible Co',
        ]);

        $response = $this->actingAs($admin)->get(route('view-companies'));

        $response->assertOk();
        $response->assertSee($adminCompany->company_name);
        $response->assertSee($ownerCompany->company_name);
    }

    public function test_seeded_users_have_expected_roles(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@softui.com']);
        $payrollManager = User::factory()->payrollManager()->create(['email' => 'payroll@softui.com']);
        $b2bAdmin = User::factory()->b2bAdmin()->forB2bFirm()->create();

        $this->assertTrue($admin->hasRole(UserRole::Admin));
        $this->assertTrue($payrollManager->hasRole(UserRole::PayrollManager));
        $this->assertTrue($b2bAdmin->hasRole(UserRole::B2bAdmin));
    }
}
