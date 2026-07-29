<?php

namespace Tests\Feature;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Http\Livewire\PayrollRunDetail;
use App\Http\Livewire\PayrollRunList;
use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Models\StructureComponent;
use App\Models\User;
use App\Services\Attendance\AttendanceSetupService;
use App\Services\Payroll\PayrollGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollRunUiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private PayrollRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->company = Company::factory()->ownedBy($user)->create();
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $location = Location::factory()->create(['company_id' => $this->company->id]);

        $basicComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Basic',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        $pfComponent = CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'PF',
            'component_type' => ComponentType::DEDUCTION,
            'default_calculation_type' => CalculationType::PERCENT_BASIC,
            'default_value' => 12,
            'display_order' => 2,
        ]);

        CompensationComponent::create([
            'company_id' => $this->company->id,
            'component_name' => 'Performance Bonus',
            'component_type' => ComponentType::EARNING,
            'default_calculation_type' => CalculationType::FIXED,
            'is_payroll_adjustment' => true,
            'display_order' => 10,
        ]);

        $structure = CompensationStructure::create([
            'company_id' => $this->company->id,
            'structure_name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $basicComponent->id,
            'value' => 30000,
            'calculation_type' => CalculationType::FIXED,
            'display_order' => 1,
        ]);

        StructureComponent::create([
            'structure_id' => $structure->id,
            'component_id' => $pfComponent->id,
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

        app(AttendanceSetupService::class)->seedCompanyDefaults($this->company->id);

        MonthlyAttendance::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'month' => 6,
            'year' => 2026,
            'entry_source' => 'manual',
            'total_days' => 30,
            'working_days' => 30,
            'present_days' => 28,
            'worked_days' => 28,
        ]);

        $service = app(PayrollGenerationService::class);
        $this->run = $service->findOrCreateRun($this->company->id, 6, 2026);
        $service->processEmployee($this->run, $this->employee);
    }

    public function test_salary_generator_page_renders_action_buttons(): void
    {
        $this->get(route('salary-generator', ['company_id' => $this->company->id]))
            ->assertOk()
            ->assertSee('Open Payroll Run')
            ->assertSee('View History')
            ->assertSee('wire:click="createRun"', false)
            ->assertSee('wire:click="openRun', false);
    }

    public function test_create_run_button_redirects_to_detail_page(): void
    {
        Livewire::test(PayrollRunList::class, ['company_id' => (string) $this->company->id])
            ->set('createMonth', 6)
            ->set('createYear', 2026)
            ->call('createRun')
            ->assertRedirect(route('payroll-run-detail', [
                'company_id' => $this->company->id,
                'run_id' => $this->run->id,
            ]));
    }

    public function test_open_run_button_redirects_to_detail_page(): void
    {
        Livewire::test(PayrollRunList::class, ['company_id' => (string) $this->company->id])
            ->call('openRun', $this->run->id)
            ->assertRedirect(route('payroll-run-detail', [
                'company_id' => $this->company->id,
                'run_id' => $this->run->id,
            ]));
    }

    public function test_payroll_run_detail_page_renders_tabs_and_actions(): void
    {
        $this->get(route('payroll-run-detail', [
            'company_id' => $this->company->id,
            'run_id' => $this->run->id,
        ]))
            ->assertOk()
            ->assertSee('All Payroll Runs')
            ->assertSee('Employees')
            ->assertSee('Adjustments')
            ->assertSee('History & Audit')
            ->assertSee('wire:click="setTab', false)
            ->assertSee('wire:click="processPayroll"', false)
            ->assertSee('wire:click="approveAll"', false);
    }

    public function test_tab_buttons_switch_active_tab(): void
    {
        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])
            ->assertSet('activeTab', 'employees')
            ->call('setTab', 'adjustments')
            ->assertSet('activeTab', 'adjustments')
            ->call('setTab', 'history')
            ->assertSet('activeTab', 'history')
            ->call('setTab', 'employees')
            ->assertSet('activeTab', 'employees');
    }

    public function test_process_payroll_button_starts_batch(): void
    {
        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])
            ->call('processPayroll')
            ->assertSet('batchStatus', 'processing');
    }

    public function test_approve_all_button_approves_draft_records(): void
    {
        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])->call('approveAll');

        $this->assertSame(
            0,
            EmployeePayroll::query()->where('payroll_run_id', $this->run->id)->where('status', EmployeePayrollStatus::DRAFT)->count()
        );
        $this->assertSame(
            1,
            EmployeePayroll::query()->where('payroll_run_id', $this->run->id)->where('status', EmployeePayrollStatus::APPROVED)->count()
        );
    }

    public function test_complete_lock_and_mark_paid_buttons_work(): void
    {
        $service = app(PayrollGenerationService::class);
        $service->approveAllDraft($this->run->fresh());

        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])->call('completeRun');

        $this->assertSame(PayrollRunStatus::COMPLETED, $this->run->fresh()->status);

        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])->call('markAllPaid');

        $this->assertSame(
            1,
            EmployeePayroll::query()->where('payroll_run_id', $this->run->id)->where('status', EmployeePayrollStatus::PAID)->count()
        );

        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])->call('lockRun');

        $this->assertSame(PayrollRunStatus::LOCKED, $this->run->fresh()->status);
    }

    public function test_review_button_redirects_to_employee_payroll_detail(): void
    {
        $payroll = EmployeePayroll::query()->where('payroll_run_id', $this->run->id)->firstOrFail();

        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])
            ->call('viewEmployeePayroll', $payroll->id)
            ->assertRedirect(route('employee-payroll-detail', [
                'company_id' => $this->company->id,
                'run_id' => $this->run->id,
                'employee_payroll_id' => $payroll->id,
            ]));
    }

    public function test_save_adjustment_matrix_button_action_persists_changes(): void
    {
        $bonusComponent = CompensationComponent::query()
            ->where('company_id', $this->company->id)
            ->where('is_payroll_adjustment', true)
            ->firstOrFail();

        Livewire::test(PayrollRunDetail::class, [
            'company_id' => (string) $this->company->id,
            'run_id' => $this->run->id,
        ])
            ->call('setTab', 'adjustments')
            ->call('saveAdjustmentMatrix', [
                [
                    'employee_id' => $this->employee->id,
                    'component_id' => $bonusComponent->id,
                    'amount' => 2500,
                ]
            ]);

        $this->assertDatabaseHas('payroll_adjustments', [
            'employee_id' => $this->employee->id,
            'payroll_run_id' => $this->run->id,
            'component_id' => $bonusComponent->id,
            'amount' => 2500,
        ]);
    }

    public function test_navigation_links_on_payroll_pages_are_reachable(): void
    {
        $service = app(PayrollGenerationService::class);
        $service->approveAllDraft($this->run->fresh());
        $service->completeRunIfReady($this->run->fresh());
        $payroll = EmployeePayroll::query()->where('payroll_run_id', $this->run->id)->firstOrFail();

        $this->get(route('payroll-history', ['company_id' => $this->company->id]))->assertOk();
        $this->get(route('salary-generator', ['company_id' => $this->company->id]))->assertOk();
        $this->get(route('compensation', ['company_id' => $this->company->id]))->assertOk();
        $this->get(route('payroll.payslip.bulk', [
            'company_id' => $this->company->id,
            'run_id' => $this->run->id,
        ]))->assertOk();
        $this->get(route('payroll.payslip', [
            'company_id' => $this->company->id,
            'run_id' => $this->run->id,
            'employee_payroll_id' => $payroll->id,
        ]))->assertOk();
    }
}
