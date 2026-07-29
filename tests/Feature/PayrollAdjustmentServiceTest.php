<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Payroll\PayrollAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private PayrollRun $run;

    private CompensationComponent $bonusComponent;

    private CompensationComponent $basicComponent;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->bonusComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Performance Bonus',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'is_payroll_adjustment' => true,
            'display_order' => 1,
        ]);

        $this->basicComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'is_payroll_adjustment' => false,
            'display_order' => 2,
        ]);

        $this->run = PayrollRun::create([
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'status' => 'DRAFT',
        ]);
    }

    public function test_create_bulk_saves_adjustments_with_component(): void
    {
        $service = app(PayrollAdjustmentService::class);

        $result = $service->createBulk($this->run, [
            [
                'employee_id' => (string) $this->employee->id,
                'component_id' => (string) $this->bonusComponent->id,
                'amount' => '5000',
                'remarks' => 'Q2 bonus',
            ],
        ]);

        $this->assertSame(['saved' => 1, 'skipped' => 0], $result);
        $this->assertDatabaseHas('payroll_adjustments', [
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $this->bonusComponent->id,
            'adjustment_type' => PayrollAdjustmentType::ADDITION->value,
            'amount' => 5000,
            'remarks' => 'Q2 bonus',
        ]);
    }

    public function test_create_bulk_rejects_structure_component(): void
    {
        $service = app(PayrollAdjustmentService::class);

        $this->expectException(ValidationException::class);

        $service->createBulk($this->run, [
            [
                'employee_id' => (string) $this->employee->id,
                'component_id' => (string) $this->basicComponent->id,
                'amount' => '1000',
                'remarks' => '',
            ],
        ]);
    }

    public function test_adjustment_type_derived_from_component_type(): void
    {
        $deductionComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Advance Recovery',
            'component_type' => ComponentType::DEDUCTION,
            'default_calculation_type' => CalculationType::FIXED,
            'is_payroll_adjustment' => true,
            'display_order' => 3,
        ]);

        app(PayrollAdjustmentService::class)->createBulk($this->run, [
            [
                'employee_id' => (string) $this->employee->id,
                'component_id' => (string) $deductionComponent->id,
                'amount' => '2000',
                'remarks' => '',
            ],
        ]);

        $adjustment = PayrollAdjustment::query()->first();
        $this->assertSame(PayrollAdjustmentType::DEDUCTION, $adjustment->adjustment_type);
    }

    public function test_create_bulk_requires_adjustment_components_to_exist(): void
    {
        CompensationComponent::query()->delete();

        $service = app(PayrollAdjustmentService::class);

        $this->expectException(ValidationException::class);

        $service->createBulk($this->run, [
            [
                'employee_id' => (string) $this->employee->id,
                'component_id' => '1',
                'amount' => '500',
                'remarks' => '',
            ],
        ]);
    }

    public function test_sync_matrix_upserts_and_deletes(): void
    {
        $service = app(PayrollAdjustmentService::class);

        $service->syncMatrix($this->run, [
            [
                'employee_id' => $this->employee->id,
                'component_id' => $this->bonusComponent->id,
                'amount' => 5000,
            ],
        ]);

        $this->assertDatabaseHas('payroll_adjustments', [
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $this->bonusComponent->id,
            'amount' => 5000,
        ]);

        $service->syncMatrix($this->run, [
            [
                'employee_id' => $this->employee->id,
                'component_id' => $this->bonusComponent->id,
                'amount' => 7500,
            ],
        ]);

        $this->assertDatabaseHas('payroll_adjustments', [
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $this->bonusComponent->id,
            'amount' => 7500,
        ]);

        $service->syncMatrix($this->run, [
            [
                'employee_id' => $this->employee->id,
                'component_id' => $this->bonusComponent->id,
                'amount' => null,
            ],
        ]);

        $this->assertDatabaseMissing('payroll_adjustments', [
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $this->bonusComponent->id,
        ]);
    }

    public function test_build_matrix_includes_employee_and_adjustment_values(): void
    {
        PayrollAdjustment::create([
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $this->bonusComponent->id,
            'adjustment_type' => PayrollAdjustmentType::ADDITION,
            'amount' => 1200,
            'created_by' => auth()->id(),
        ]);

        $matrix = app(PayrollAdjustmentService::class)->buildMatrix($this->run);

        $this->assertCount(1, $matrix['components']);
        $this->assertSame($this->employee->id, $matrix['rowData'][0]['employee_id']);
        $this->assertSame(1200.0, $matrix['rowData'][0]['adj_'.$this->bonusComponent->id]);
    }
}
