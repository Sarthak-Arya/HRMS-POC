<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Support\RolePermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_each_role_has_expected_feature_permissions(): void
    {
        $matrix = RolePermissions::matrix();

        $this->assertContains(Permission::UsersManage->value, $matrix[UserRole::Admin->value]);
        $this->assertNotContains(Permission::UsersManage->value, $matrix[UserRole::PayrollManager->value]);
        $this->assertContains(Permission::CompensationManage->value, $matrix[UserRole::Accountant->value]);
        $this->assertNotContains(Permission::CompensationManage->value, $matrix[UserRole::HrManager->value]);
        $this->assertNotContains(Permission::SalaryGenerate->value, $matrix[UserRole::Viewer->value]);
    }

    public function test_viewer_cannot_open_salary_generator(): void
    {
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->create(['company_handled_by' => $viewer->id]);

        $response = $this->actingAs($viewer)->get(route('salary-generator', ['company_id' => $company->id]));

        $response->assertForbidden();
    }

    public function test_accountant_can_open_compensation_but_not_add_employee(): void
    {
        $accountant = User::factory()->accountant()->create();
        $company = Company::factory()->create(['company_handled_by' => $accountant->id]);

        $this->actingAs($accountant)
            ->get(route('compensation', ['company_id' => $company->id]))
            ->assertOk();

        $this->actingAs($accountant)
            ->get(route('add-employee-details', ['company_id' => $company->id]))
            ->assertForbidden();
    }

    public function test_hr_manager_can_manage_employees_and_attendance(): void
    {
        $hrManager = User::factory()->hrManager()->create();
        $company = Company::factory()->create(['company_handled_by' => $hrManager->id]);

        $this->actingAs($hrManager)
            ->get(route('add-employee-details', ['company_id' => $company->id]))
            ->assertOk();

        $this->actingAs($hrManager)
            ->get(route('attendance-entry', ['company_id' => $company->id]))
            ->assertOk();

        $this->actingAs($hrManager)
            ->get(route('salary-generator', ['company_id' => $company->id]))
            ->assertForbidden();
    }

    public function test_admin_bypasses_permission_checks(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->assertTrue($admin->hasPermission(Permission::SalaryGenerate));
        $this->assertTrue($admin->hasPermission(Permission::UsersManage));
        $this->assertTrue($admin->canAccessCompany($company));

        $this->actingAs($admin)
            ->get(route('dashboard', ['company_id' => $company->id]))
            ->assertOk();
    }
}
