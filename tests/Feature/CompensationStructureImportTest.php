<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Http\Livewire\CompensationHub;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\StructureComponent;
use App\Models\User;
use App\Services\Compensation\CompensationStructureImportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompensationStructureImportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private CompensationComponent $basic;
    private CompensationComponent $hra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->company = Company::factory()->ownedBy($user)->create();

        $this->basic = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'default_value' => 0,
            'is_active' => true,
            'display_order' => 1,
        ]);

        $this->hra = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'HRA',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::PERCENT_BASIC,
            'default_value' => 0,
            'is_active' => true,
            'display_order' => 2,
        ]);
    }

    public function test_import_creates_structure_from_grouped_rows(): void
    {
        $rows = [
            [
                '_row_number' => 2,
                'structure_name' => 'Standard',
                'effective_from' => now()->toDateString(),
                'effective_to' => '',
                'is_default' => '0',
                'component_name' => 'Basic',
                'calculation_type' => 'FIXED',
                'value' => '30000',
                'display_order' => '1',
            ],
            [
                '_row_number' => 3,
                'structure_name' => 'Standard',
                'effective_from' => now()->toDateString(),
                'effective_to' => '',
                'is_default' => '0',
                'component_name' => 'HRA',
                'calculation_type' => 'PERCENT_BASIC',
                'value' => '40',
                'display_order' => '2',
            ],
        ];

        $result = app(CompensationStructureImportService::class)->import($this->company->id, $rows);

        $this->assertSame(1, $result['total_groups']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('compensation_structures', [
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
        ]);

        $structure = CompensationStructure::where('company_id', $this->company->id)
            ->where('structure_name', 'Standard')
            ->firstOrFail();

        $this->assertDatabaseHas('structure_components', [
            'structure_id' => $structure->id,
            'component_id' => $this->basic->id,
        ]);
        $this->assertDatabaseHas('structure_components', [
            'structure_id' => $structure->id,
            'component_id' => $this->hra->id,
        ]);
    }

    public function test_import_fails_when_structure_name_already_exists(): void
    {
        $existing = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'is_default' => false,
        ]);

        StructureComponent::create([
            'structure_id' => $existing->id,
            'component_id' => $this->basic->id,
            'value' => 25000,
            'calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        $rows = [[
            '_row_number' => 2,
            'structure_name' => 'Standard',
            'effective_from' => now()->toDateString(),
            'component_name' => 'HRA',
            'calculation_type' => 'PERCENT_BASIC',
            'value' => '40',
            'display_order' => '1',
        ]];

        $result = app(CompensationStructureImportService::class)->import($this->company->id, $rows);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('already exists', implode(' ', $result['errors']));
        $this->assertSame(1, CompensationStructure::where('company_id', $this->company->id)->where('structure_name', 'Standard')->count());
    }

    public function test_import_reports_missing_component_name_errors(): void
    {
        $rows = [[
            '_row_number' => 2,
            'structure_name' => 'New Structure',
            'effective_from' => now()->toDateString(),
            'component_name' => 'Travel Allowance',
            'calculation_type' => 'FIXED',
            'value' => '1200',
            'display_order' => '1',
        ]];

        $result = app(CompensationStructureImportService::class)->import($this->company->id, $rows);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('not found or inactive', implode(' ', $result['errors']));
        $this->assertDatabaseMissing('compensation_structures', [
            'company_id' => $this->company->id,
            'structure_name' => 'New Structure',
        ]);
    }

    public function test_import_supports_mixed_success_and_failures(): void
    {
        CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Existing Structure',
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'is_default' => false,
        ]);

        $rows = [
            [
                '_row_number' => 2,
                'structure_name' => 'Existing Structure',
                'effective_from' => now()->toDateString(),
                'component_name' => 'Basic',
                'calculation_type' => 'FIXED',
                'value' => '10000',
                'display_order' => '1',
            ],
            [
                '_row_number' => 3,
                'structure_name' => 'New Structure',
                'effective_from' => now()->toDateString(),
                'component_name' => 'Basic',
                'calculation_type' => 'FIXED',
                'value' => '20000',
                'display_order' => '1',
            ],
        ];

        $result = app(CompensationStructureImportService::class)->import($this->company->id, $rows);

        $this->assertSame(2, $result['total_groups']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['failed']);
        $this->assertDatabaseHas('compensation_structures', [
            'company_id' => $this->company->id,
            'structure_name' => 'New Structure',
        ]);
    }

    public function test_template_download_action_is_available(): void
    {
        Livewire::test(CompensationHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'structures')
            ->call('downloadStructureTemplate')
            ->assertFileDownloaded('compensation_structure_template.xlsx');
    }
}
