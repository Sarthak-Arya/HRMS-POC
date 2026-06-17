<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Payroll\EmployeeLoanStatus;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeeLoan;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\StructureComponent;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private CompensationComponent $basicComponent;

    private CompensationComponent $pfComponent;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->create(['company_handled_by' => $user->id]);
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->basicComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        $this->pfComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'PF',
            'component_type' => ComponentType::DEDUCTION,
            'default_calculation_type' => CalculationType::PERCENT_BASIC,
            'default_value' => 12,
            'display_order' => 2,
        ]);

        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $this->basicComponent->id,
            'value' => 30000,
            'calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $this->pfComponent->id,
            'value' => 12,
            'calculation_type' => CalculationType::PERCENT_BASIC,
            'display_order' => 2,
        ]);

        CompensationStructureAssignment::create([
            'company_id' => $this->company->id,
            'scope_type' => CompensationScopeType::COMPANY,
            'scope_id' => null,
            'structure_id' => $structure->id,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'location_id' => $location->id,
        ]);

        EmployeeCompensationHistory::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'structure_id' => $structure->id,
            'annual_ctc' => 360000,
            'monthly_gross' => 30000,
            'effective_from' => now()->subYear()->toDateString(),
        ]);

        MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'total_days' => 30,
            'worked_days' => 28,
        ]);
    }

    public function test_process_employee_generates_payroll_with_proration(): void
    {
        $service = app(PayrollGenerationService::class);
        $run = $service->findOrCreateRun($this->company->id, 6, 2026);

        $payroll = $service->processEmployee($run, $this->employee);

        $this->assertNotNull($payroll);
        $this->assertSame(EmployeePayrollStatus::DRAFT, $payroll->status);
        $this->assertGreaterThan(0, (float) $payroll->gross_earnings);
        $this->assertGreaterThan(0, (float) $payroll->gross_deductions);
        $this->assertCount(2, $payroll->lines);
    }

    public function test_adjustment_and_loan_affect_net_pay(): void
    {
        $service = app(PayrollGenerationService::class);
        $run = $service->findOrCreateRun($this->company->id, 6, 2026);

        PayrollAdjustment::create([
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $run->id,
            'adjustment_type' => PayrollAdjustmentType::ADDITION,
            'amount' => 5000,
            'remarks' => 'Bonus',
            'created_by' => auth()->id(),
        ]);

        EmployeeLoan::create([
            'employee_id' => $this->employee->id,
            'loan_name' => 'Advance',
            'principal_amount' => 10000,
            'emi_amount' => 2000,
            'start_month' => 6,
            'start_year' => 2026,
            'remaining_amount' => 10000,
            'status' => EmployeeLoanStatus::ACTIVE,
        ]);

        $payroll = $service->processEmployee($run, $this->employee);
        $this->assertNotNull($payroll);
        $this->assertTrue($payroll->lines->contains(fn ($line) => str_contains($line->component_name, 'Bonus') || str_contains($line->component_name, 'Adjustment')));
        $this->assertTrue($payroll->lines->contains(fn ($line) => str_contains($line->component_name, 'EMI')));
    }

    public function test_complete_and_lock_lifecycle(): void
    {
        $service = app(PayrollGenerationService::class);
        $run = $service->findOrCreateRun($this->company->id, 6, 2026);
        $service->processEmployee($run, $this->employee);
        $service->approveAllDraft($run->fresh());

        $completed = $service->completeRunIfReady($run->fresh());
        $this->assertSame(PayrollRunStatus::COMPLETED, $completed->status);

        $locked = $service->lockRun($completed->fresh());
        $this->assertSame(PayrollRunStatus::LOCKED, $locked->status);

        $payroll = EmployeePayroll::first();
        $payroll->gross_earnings = 99999;
        $this->expectException(\App\Exceptions\Payroll\PayrollLifecycleException::class);
        $payroll->save();
    }

    public function test_payslip_route_returns_document(): void
    {
        $service = app(PayrollGenerationService::class);
        $run = $service->findOrCreateRun($this->company->id, 6, 2026);
        $payroll = $service->processEmployee($run, $this->employee);
        $service->approveAllDraft($run->fresh());
        $service->completeRunIfReady($run->fresh());

        $response = $this->get(route('payroll.payslip', [
            'company_id' => $this->company->id,
            'run_id' => $run->id,
            'employee_payroll_id' => $payroll->id,
        ]));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-type'), 'pdf')
            || str_contains($response->headers->get('content-type'), 'html')
        );
    }

    public function test_payroll_run_list_page_loads(): void
    {
        $response = $this->get(route('salary-generator', ['company_id' => $this->company->id]));

        $response->assertOk();
    }
}
