<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Settings\CompanySettingsSection;
use App\Enums\UserRole;
use App\Http\Livewire\SettingsHub;
use App\Models\Company;
use App\Models\CompanySettingsAuditLog;
use App\Models\User;
use App\Services\Settings\CompanySettingsService;
use App\Support\RolePermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_role_matrix_includes_settings_permissions(): void
    {
        $matrix = RolePermissions::matrix();

        $this->assertContains(Permission::SettingsView->value, $matrix[UserRole::CompanyAdmin->value]);
        $this->assertContains(Permission::SettingsManageCompanyProfile->value, $matrix[UserRole::CompanyAdmin->value]);
        $this->assertContains(Permission::SettingsManageOrganization->value, $matrix[UserRole::HrManager->value]);
        $this->assertContains(Permission::SettingsManageAttendance->value, $matrix[UserRole::HrManager->value]);
        $this->assertContains(Permission::SettingsManageTax->value, $matrix[UserRole::CompanyAdmin->value]);
        $this->assertContains(Permission::SettingsManageTax->value, $matrix[UserRole::Accountant->value]);
        $this->assertNotContains(Permission::SettingsManageCompanyProfile->value, $matrix[UserRole::HrManager->value]);
        $this->assertNotContains(Permission::SettingsView->value, $matrix[UserRole::Viewer->value]);
    }

    public function test_company_owner_can_open_settings_page(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->get(route('settings', ['company_id' => $company->id]))
            ->assertOk();
    }

    public function test_viewer_cannot_open_settings_page(): void
    {
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->ownedBy($viewer)->create();

        $this->actingAs($viewer)
            ->get(route('settings', ['company_id' => $company->id]))
            ->assertForbidden();
    }

    public function test_user_cannot_access_another_companys_settings(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $otherOwner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($otherOwner)->create();

        $this->actingAs($owner)
            ->get(route('settings', ['company_id' => $company->id]))
            ->assertForbidden();
    }

    public function test_hr_can_save_organization_and_attendance_but_not_company_profile(): void
    {
        $hr = User::factory()->hrManager()->create();
        $company = Company::factory()->ownedBy($hr)->create();

        Livewire::actingAs($hr)
            ->test(SettingsHub::class, ['company_id' => (string) $company->id])
            ->set('requireLocation', true)
            ->call('saveOrganizationPreferences')
            ->assertHasNoErrors();

        Livewire::actingAs($hr)
            ->test(SettingsHub::class, ['company_id' => (string) $company->id])
            ->set('attendanceShowDailyMarking', false)
            ->call('saveAttendancePreferences')
            ->assertHasNoErrors();

        Livewire::actingAs($hr)
            ->test(SettingsHub::class, ['company_id' => (string) $company->id])
            ->set('companyName', 'Updated Name')
            ->call('saveCompanyProfile')
            ->assertForbidden();
    }

    public function test_owner_can_save_company_profile(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create([
            'company_name' => 'Original Name'
        ]);

        Livewire::actingAs($owner)
            ->test(SettingsHub::class, ['company_id' => (string) $company->id])
            ->set('companyName', 'Updated Name')
            ->set('addressLine1', '123 Updated Street')
            ->set('city', 'New Delhi')
            ->set('state', 'Delhi')
            ->set('zipCode', '110001')
            ->set('businessLocation', 'India')
            ->set('industry', 'Consulting')
            ->call('saveCompanyProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('company', [
            'id' => $company->id,
            'company_name' => 'Updated Name',
        ]);

        $company->refresh();
        $this->assertStringContainsString('123 Updated Street', (string) $company->company_address);
    }

    public function test_settings_page_accepts_organization_profile_category(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->get(route('settings', [
                'company_id' => $company->id,
                'category' => 'organization-profile',
            ]))
            ->assertOk()
            ->assertSee('Organisation Profile')
            ->assertSee('Branding')
            ->assertSee('Work Locations');
    }

    public function test_settings_validator_rejects_invalid_attendance_payload(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanySettingsService::class);

        $this->expectException(ValidationException::class);

        $service->updateSection(
            $company->id,
            CompanySettingsSection::Attendance,
            [
                'defaultView' => 'invalid_view',
                'monthLockEnforced' => true,
                'showDailyMarking' => true,
                'defaultPolicyId' => null,
            ],
            $service->currentVersion($company->id),
        );
    }

    public function test_section_update_creates_audit_log(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanySettingsService::class);

        $service->updateSection(
            $company->id,
            CompanySettingsSection::Reports,
            [
                'defaultExportFormat' => 'csv',
                'includeCompanyLogo' => true,
                'defaultTemplateCategory' => 'payroll',
            ],
            $service->currentVersion($company->id),
            null,
            'Test update',
        );

        $this->assertDatabaseHas('company_settings_audit_logs', [
            'company_id' => $company->id,
            'section' => CompanySettingsSection::Reports->value,
            'reason' => 'Test update',
        ]);

        $log = CompanySettingsAuditLog::query()->where('company_id', $company->id)->first();
        $this->assertSame('csv', $log->after_json['defaultExportFormat']);
    }

    public function test_settings_backfill_creates_defaults_for_company(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanySettingsService::class);
        $settings = $service->getForCompany($company->id);

        $this->assertSame('Asia/Kolkata', $settings['companyProfile']['timezone']);
        $this->assertSame('monthly_summary', $settings['attendance']['defaultView']);
        $this->assertSame('xlsx', $settings['reports']['defaultExportFormat']);
        $this->assertSame('monthly', $settings['tax']['taxPaymentFrequency']);
        $this->assertSame('employee', $settings['tax']['deductorType']);
    }

    public function test_owner_can_save_tax_details(): void
    {
        $owner = User::factory()->companyAdmin()->create();
        $company = Company::factory()->ownedBy($owner)->create();

        Livewire::actingAs($owner)
            ->test(SettingsHub::class, [
                'company_id' => (string) $company->id,
                'category' => 'tax',
            ])
            ->assertSee('Tax Details')
            ->set('taxPan', 'ABCDE1234F')
            ->set('taxTan', 'ABCD12345E')
            ->set('taxTdsCircleArea', 'DEL')
            ->set('taxTdsCircleRange', 'WW')
            ->set('taxTdsCircleNumber', '101')
            ->set('taxTdsCircleAo', '01')
            ->set('taxPaymentFrequency', 'quarterly')
            ->set('taxDeductorType', 'non_employee')
            ->set('taxDeductorName', 'Ravi Kumar')
            ->set('taxDeductorFatherName', 'Suresh Kumar')
            ->call('saveTaxDetails')
            ->assertHasNoErrors();

        $tax = app(CompanySettingsService::class)->getSection($company->id, CompanySettingsSection::Tax);

        $this->assertSame('ABCDE1234F', $tax['pan']);
        $this->assertSame('ABCD12345E', $tax['tan']);
        $this->assertSame('DEL', $tax['tdsCircleArea']);
        $this->assertSame('quarterly', $tax['taxPaymentFrequency']);
        $this->assertSame('non_employee', $tax['deductorType']);
        $this->assertSame('Ravi Kumar', $tax['deductorName']);
    }

    public function test_hr_cannot_save_tax_details(): void
    {
        $hr = User::factory()->hrManager()->create();
        $company = Company::factory()->ownedBy($hr)->create();

        Livewire::actingAs($hr)
            ->test(SettingsHub::class, [
                'company_id' => (string) $company->id,
                'category' => 'tax',
            ])
            ->set('taxPan', 'ABCDE1234F')
            ->call('saveTaxDetails')
            ->assertForbidden();
    }

    public function test_settings_validator_rejects_invalid_pan(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanySettingsService::class);

        $this->expectException(ValidationException::class);

        $service->updateSection(
            $company->id,
            CompanySettingsSection::Tax,
            [
                'pan' => 'INVALID',
                'tan' => null,
                'tdsCircleArea' => null,
                'tdsCircleRange' => null,
                'tdsCircleNumber' => null,
                'tdsCircleAo' => null,
                'taxPaymentFrequency' => 'monthly',
                'deductorType' => 'employee',
                'deductorName' => null,
                'deductorFatherName' => null,
            ],
            $service->currentVersion($company->id),
        );
    }

    public function test_hr_can_manage_organization_structure(): void
    {
        $hr = User::factory()->hrManager()->create();
        $company = Company::factory()->ownedBy($hr)->create();

        Livewire::actingAs($hr)
            ->test(SettingsHub::class, [
                'company_id' => (string) $company->id,
                'category' => 'organization-profile',
            ])
            ->set('orgSubTab', 'departments')
            ->set('departmentName', 'Engineering')
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'company_id' => $company->id,
            'department_name' => 'Engineering',
        ]);
    }
}
