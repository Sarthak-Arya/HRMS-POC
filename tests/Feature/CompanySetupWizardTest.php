<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\StatutoryComponent;
use App\Enums\Settings\CompanySettingsSection;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\AuthLandingService;
use App\Services\Settings\CompanySettingsService;
use App\Services\Setup\CompanySetupProgressService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySetupWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_hr_manager_cannot_access_view_companies(): void
    {
        $hr = User::factory()->hrManager()->create();
        Company::factory()->ownedBy($hr)->create();

        $this->actingAs($hr)
            ->get(route('view-companies'))
            ->assertForbidden();
    }

    public function test_company_admin_cannot_access_view_companies(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        Company::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->get(route('view-companies'))
            ->assertForbidden();
    }

    public function test_b2b_admin_can_access_view_companies(): void
    {
        $firmAdmin = User::factory()->b2bAdmin()->forB2bFirm()->create();
        Company::factory()->forFirm($firmAdmin->b2b_firm_id)->create();

        $this->actingAs($firmAdmin)
            ->get(route('view-companies'))
            ->assertOk();
    }

    public function test_b2b_staff_can_access_view_companies(): void
    {
        $staff = User::factory()->b2bStaff()->forB2bFirm()->create();
        Company::factory()->forFirm($staff->b2b_firm_id)->create();

        $this->actingAs($staff)
            ->get(route('view-companies'))
            ->assertOk();
    }

    public function test_hr_with_incomplete_company_lands_on_getting_started(): void
    {
        $hr = User::factory()->hrManager()->create();
        $company = Company::factory()->ownedBy($hr)->create([
            'company_name' => 'HR Co',
            'company_address' => 'Somewhere'
        ]);
        app(CompanySettingsService::class)->ensureExists($company->id);

        $route = app(AuthLandingService::class)->homeRoute($hr);

        $this->assertSame(
            route('getting-started', ['company_id' => $company->id]),
            $route,
        );
    }

    public function test_company_admin_with_single_incomplete_company_lands_on_getting_started(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'Owner Co',
            'company_address' => 'Address line',
            'is_pf' => true,
            'is_esi' => false
        ]);
        app(CompanySettingsService::class)->ensureExists($company->id);

        $route = app(AuthLandingService::class)->homeRoute($owner);

        $this->assertSame(
            route('getting-started', ['company_id' => $company->id]),
            $route,
        );
    }

    public function test_b2b_admin_with_multiple_companies_lands_on_view_companies(): void
    {
        $firmAdmin = User::factory()->b2bAdmin()->forB2bFirm()->create();
        Company::factory()->forFirm($firmAdmin->b2b_firm_id)->create([
            'company_name' => 'A Co',
        ]);
        Company::factory()->forFirm($firmAdmin->b2b_firm_id)->create([
            'company_name' => 'B Co',
        ]);

        $route = app(AuthLandingService::class)->homeRoute($firmAdmin);

        $this->assertSame(route('view-companies'), $route);
    }

    public function test_company_admin_without_company_lands_on_add_company(): void
    {
        $owner = User::factory()->companyAdmin()->create();

        $route = app(AuthLandingService::class)->homeRoute($owner);

        $this->assertSame(route('add-company-details'), $route);
    }

    public function test_getting_started_page_renders_for_company_handler(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'Setup Co',
            'company_address' => '1 Main St'
        ]);
        app(CompanySettingsService::class)->ensureExists($company->id);

        $this->actingAs($owner)
            ->get(route('getting-started', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('Get started with FlipCore')
            ->assertSee('Add Organisation Details')
            ->assertSee('Add Employees');
    }

    public function test_progress_detects_completed_steps(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'Ready Co',
            'company_address' => 'Ready Address',
            'is_pf' => true,
            'is_esi' => true
        ]);

        $settings = app(CompanySettingsService::class);
        $settings->ensureExists($company->id);
        $settings->updateSection(
            $company->id,
            CompanySettingsSection::CompanyProfile,
            [
                'timezone' => 'Asia/Kolkata',
                'fiscalYearStartMonth' => 4,
                'payrollCurrency' => 'INR',
                'dateFormat' => 'd M Y',
                'gstNumber' => '22AAAAA0000A1Z5',
                'zipCode' => null,
                'state' => null,
                'country' => null,
                'esiCode' => 'ESI-1',
                'esiContribution' => null,
                'esiCoverageStartDate' => null,
                'esiCoverageEndDate' => null,
                'pfCode' => 'PF-1',
                'pfContribution' => null,
                'pfCoverageStartDate' => null,
                'pfCoverageEndDate' => null,
            ],
            1,
            $owner->id,
        );

        CompensationComponent::query()->create([
            'company_id' => $company->id,
            'component_name' => 'PF',
            'component_type' => ComponentType::DEDUCTION,
            'default_calculation_type' => CalculationType::FIXED,
            'default_value' => 0,
            'statutory_component' => StatutoryComponent::PF,
            'is_active' => true,
            'display_order' => 1,
            'created_by' => $owner->id,
        ]);
        CompensationComponent::query()->create([
            'company_id' => $company->id,
            'component_name' => 'ESIC',
            'component_type' => ComponentType::DEDUCTION,
            'default_calculation_type' => CalculationType::FIXED,
            'default_value' => 0,
            'statutory_component' => StatutoryComponent::ESIC,
            'is_active' => true,
            'display_order' => 2,
            'created_by' => $owner->id,
        ]);
        CompensationComponent::query()->create([
            'company_id' => $company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'default_value' => 10000,
            'statutory_component' => null,
            'is_active' => true,
            'display_order' => 3,
            'created_by' => $owner->id,
        ]);

        Department::query()->create(['company_id' => $company->id, 'department_name' => 'Engineering']);
        Designation::query()->create(['company_id' => $company->id, 'designation_name' => 'Engineer']);
        Employee::factory()->create(['company_id' => $company->id]);

        $progress = app(CompanySetupProgressService::class)->forCompany($company->fresh());

        $this->assertTrue($progress['is_complete']);
        $this->assertSame(7, $progress['completed_count']);
    }
}
