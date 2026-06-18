<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\CompensationScopeType;
use App\Http\Livewire\CompensationHub;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\StructureComponent;
use App\Models\User;
use App\Services\Compensation\CompensationAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompensationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private CompensationStructure $structure;
    private CompensationComponent $basicComponent;
    private CompensationComponent $hraComponent;
    private Department $departmentA;
    private Department $departmentB;
    private Department $departmentC;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->company = Company::factory()->create(['company_handled_by' => $user->id]);

        $this->basicComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        $this->hraComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'HRA',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::PERCENT_BASIC,
            'display_order' => 2,
        ]);

        $this->structure = $this->makeStructure('Standard', [
            [$this->basicComponent, 30000, CalculationType::FIXED],
            [$this->hraComponent, 40, CalculationType::PERCENT_BASIC],
        ]);

        $this->departmentA = Department::factory()->create(['company_id' => $this->company->id]);
        $this->departmentB = Department::factory()->create(['company_id' => $this->company->id]);
        $this->departmentC = Department::factory()->create(['company_id' => $this->company->id]);

        Location::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_assign_bulk_creates_one_assignment_per_scope(): void
    {
        $service = app(CompensationAssignmentService::class);
        $effectiveFrom = now()->toDateString();

        $result = $service->assignBulk($this->company->id, [
            'scope_type' => 'department',
            'structure_id' => $this->structure->id,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
        ], [$this->departmentA->id, $this->departmentB->id]);

        $this->assertCount(2, $result['created']);
        $this->assertCount(0, $result['failed']);
        $this->assertDatabaseHas('compensation_structure_assignments', [
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::DEPARTMENT->value,
            'scope_id' => $this->departmentA->id,
            'structure_id' => $this->structure->id,
        ]);
        $this->assertDatabaseHas('compensation_structure_assignments', [
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::DEPARTMENT->value,
            'scope_id' => $this->departmentB->id,
            'structure_id' => $this->structure->id,
        ]);
    }

    public function test_assign_bulk_continues_on_partial_failure(): void
    {
        CompensationStructureAssignment::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::DEPARTMENT,
            'scope_id' => $this->departmentC->id,
            'structure_id' => $this->structure->id,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        $service = app(CompensationAssignmentService::class);
        $effectiveFrom = now()->toDateString();

        $result = $service->assignBulk($this->company->id, [
            'scope_type' => 'department',
            'structure_id' => $this->structure->id,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
        ], [$this->departmentA->id, $this->departmentC->id]);

        $this->assertCount(1, $result['created']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame($this->departmentC->id, $result['failed'][0]['scope_id']);
        $this->assertDatabaseHas('compensation_structure_assignments', [
            'scope_id' => $this->departmentA->id,
            'effective_from' => $effectiveFrom,
        ]);
    }

    public function test_selecting_structure_loads_assignment_rows_in_view_mode(): void
    {
        Livewire::test(CompensationHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'assignments')
            ->set('assignmentStructureId', (string) $this->structure->id)
            ->assertSet('assignmentStructureLoaded', true)
            ->assertSet('assignmentStructureEditing', false)
            ->assertCount('assignmentStructureRows', 2)
            ->assertSet('assignmentStructureRows.0.component_id', (string) $this->basicComponent->id);
    }

    public function test_save_assignment_creates_assignments_without_updating_structure(): void
    {
        Livewire::test(CompensationHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'assignments')
            ->set('assignmentScopeType', 'department')
            ->set('assignmentScopeIds', [(string) $this->departmentA->id, (string) $this->departmentB->id])
            ->set('assignmentStructureId', (string) $this->structure->id)
            ->set('assignmentEffectiveFrom', now()->toDateString())
            ->set('assignmentStructureRows.0.value', 35000)
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_components', [
            'structure_id' => $this->structure->id,
            'component_id' => $this->basicComponent->id,
            'value' => 30000,
        ]);
        $this->assertDatabaseHas('compensation_structure_assignments', [
            'scope_id' => $this->departmentA->id,
            'structure_id' => $this->structure->id,
        ]);
        $this->assertDatabaseHas('compensation_structure_assignments', [
            'scope_id' => $this->departmentB->id,
            'structure_id' => $this->structure->id,
        ]);
    }

    public function test_save_assignment_structure_updates_after_confirm(): void
    {
        Livewire::test(CompensationHub::class, ['company_id' => (string) $this->company->id])
            ->set('activeTab', 'assignments')
            ->set('assignmentStructureId', (string) $this->structure->id)
            ->call('startAssignmentStructureEdit')
            ->assertSet('assignmentStructureEditing', true)
            ->set('assignmentStructureRows.0.value', 35000)
            ->call('confirmSaveAssignmentStructure')
            ->assertSet('showAssignmentStructureSaveConfirm', true)
            ->call('saveAssignmentStructure')
            ->assertHasNoErrors()
            ->assertSet('assignmentStructureEditing', false);

        $this->assertDatabaseHas('structure_components', [
            'structure_id' => $this->structure->id,
            'component_id' => $this->basicComponent->id,
            'value' => 35000,
        ]);
    }

    /**
     * @param list<array{0: CompensationComponent, 1: float, 2: CalculationType}> $rows
     */
    private function makeStructure(string $name, array $rows): CompensationStructure
    {
        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => $name,
            'is_active' => true,
            'is_default' => false,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        foreach ($rows as $index => [$component, $value, $calcType]) {
            StructureComponent::create([
                'structure_id' => $structure->id,
                'component_id' => $component->id,
                'value' => $value,
                'calculation_type' => $calcType,
                'display_order' => $index + 1,
                'is_mandatory' => strcasecmp($component->component_name, 'Basic') === 0,
            ]);
        }

        return $structure;
    }
}
