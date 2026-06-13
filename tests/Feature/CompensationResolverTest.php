<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Compensation\OverrideType;
use App\Models\CompensationComponent;
use App\Models\CompensationOverride;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\Location;
use App\Models\StructureComponent;
use App\Models\User;
use App\Services\Compensation\CompensationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompensationResolverTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private CompensationStructure $companyStructure;
    private CompensationStructure $departmentStructure;
    private CompensationComponent $basicComponent;
    private CompensationComponent $hraComponent;
    private Department $department;
    private Location $location;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->company = Company::factory()->create(['company_handled_by' => $user->id]);
        $this->department = Department::factory()->create(['company_id' => $this->company->id]);
        $this->location = Location::factory()->create(['company_id' => $this->company->id]);

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

        $this->companyStructure = $this->makeStructure('Company Standard', [
            [$this->basicComponent, 30000, CalculationType::FIXED],
            [$this->hraComponent, 40, CalculationType::PERCENT_BASIC],
        ], true);

        $this->departmentStructure = $this->makeStructure('Department Premium', [
            [$this->basicComponent, 50000, CalculationType::FIXED],
            [$this->hraComponent, 50, CalculationType::PERCENT_BASIC],
        ]);

        CompensationStructureAssignment::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::COMPANY,
            'scope_id' => null,
            'structure_id' => $this->companyStructure->id,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        CompensationStructureAssignment::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::DEPARTMENT,
            'scope_id' => $this->department->id,
            'structure_id' => $this->departmentStructure->id,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'location_id' => $this->location->id,
        ]);
    }

    public function test_department_structure_overrides_company_assignment(): void
    {
        $resolved = app(CompensationResolver::class)->resolveForEmployee($this->employee);

        $this->assertSame($this->departmentStructure->id, $resolved->structureId);
        $this->assertSame('department_assignment', $resolved->structureSource);

        $basic = $resolved->lines->firstWhere('componentId', $this->basicComponent->id);
        $this->assertSame(50000.0, $basic->monthlyAmount);
    }

    public function test_employee_history_overrides_department_assignment(): void
    {
        EmployeeCompensationHistory::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'structure_id' => $this->companyStructure->id,
            'annual_ctc' => 600000,
            'monthly_gross' => 50000,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        $resolved = app(CompensationResolver::class)->resolveForEmployee($this->employee);

        $this->assertSame($this->companyStructure->id, $resolved->structureId);
        $this->assertSame('employee_history', $resolved->structureSource);
        $this->assertSame(600000.0, $resolved->annualCtc);
    }

    public function test_employee_override_wins_over_company_override(): void
    {
        EmployeeCompensationHistory::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'structure_id' => $this->departmentStructure->id,
            'annual_ctc' => 1200000,
            'monthly_gross' => 100000,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        CompensationOverride::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::COMPANY,
            'scope_id' => null,
            'component_id' => $this->basicComponent->id,
            'override_type' => OverrideType::REPLACE,
            'value' => 20000,
            'calculation_type' => CalculationType::FIXED,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        CompensationOverride::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::EMPLOYEE,
            'scope_id' => $this->employee->id,
            'component_id' => $this->basicComponent->id,
            'override_type' => OverrideType::REPLACE,
            'value' => 75000,
            'calculation_type' => CalculationType::FIXED,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        $resolved = app(CompensationResolver::class)->resolveForEmployee($this->employee);
        $basic = $resolved->lines->firstWhere('componentId', $this->basicComponent->id);

        $this->assertSame(75000.0, $basic->monthlyAmount);
        $this->assertSame('employee', $basic->source);
    }

    /**
     * @param list<array{0: CompensationComponent, 1: float, 2: CalculationType}> $rows
     */
    private function makeStructure(string $name, array $rows, bool $isDefault = false): CompensationStructure
    {
        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => $name,
            'is_active' => true,
            'is_default' => $isDefault,
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
