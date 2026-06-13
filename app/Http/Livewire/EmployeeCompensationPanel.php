<?php

namespace App\Http\Livewire;

use App\Models\CompensationStructure;
use App\Models\Employee;
use App\Services\Compensation\CompensationResolver;
use App\Services\Compensation\EmployeeCompensationService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Livewire component for the employee compensation management panel.
 * Handles viewing history and saving new compensation revisions.
 */
class EmployeeCompensationPanel extends Component
{
    /** @var string The ID of the company */
    public string $companyId = '';

    /** @var string The ID of the employee */
    public string $employeeId = '';

    /** @var string Selected compensation structure ID */
    public string $structureId = '';

    /** @var string|float Annual Cost to Company (CTC) */
    public $annualCtc = '';

    /** @var string|float Monthly Gross salary */
    public $monthlyGross = '';

    /** @var string Date the revision is effective from */
    public string $effectiveFrom = '';

    /** @var string Reason for the compensation revision */
    public string $revisionReason = '';

    /**
     * Initialize the component with data for a specific employee.
     *
     * @param string $companyId The ID of the company.
     * @param string $employeeId The ID of the employee.
     * @return void
     */
    public function mount(string $companyId, string $employeeId): void
    {
        $this->companyId = $companyId;
        $this->employeeId = $employeeId;
        $this->effectiveFrom = now()->toDateString();

        $active = Employee::where('company_id', $companyId)
            ->find($employeeId)
            ?->activeCompensationHistory;

        if ($active) {
            $this->structureId = (string) $active->structure_id;
            $this->annualCtc = $active->annual_ctc;
            $this->monthlyGross = $active->monthly_gross;
            $this->effectiveFrom = $active->effective_from->toDateString();
            $this->revisionReason = $active->revision_reason ?? '';
        }
    }

    /**
     * Hook called when annualCtc property is updated.
     * Automatically calculates monthly gross.
     *
     * @return void
     */
    public function updatedAnnualCtc(): void
    {
        if ($this->annualCtc !== '' && is_numeric($this->annualCtc)) {
            $this->monthlyGross = round((float) $this->annualCtc / 12, 2);
        }
    }

    /**
     * Save the compensation revision.
     *
     * @return void
     */
    public function saveRevision(): void
    {
        try {
            app(EmployeeCompensationService::class)->assignRevision(
                (int) $this->companyId,
                (int) $this->employeeId,
                [
                    'structure_id' => (int) $this->structureId,
                    'annual_ctc' => $this->annualCtc,
                    'monthly_gross' => $this->monthlyGross !== '' ? $this->monthlyGross : null,
                    'effective_from' => $this->effectiveFrom,
                    'revision_reason' => $this->revisionReason ?: null,
                ],
            );
            session()->flash('compensation_success', 'Compensation revision saved.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View The rendered view.
     */
    public function render()
    {
        $employee = Employee::with(['department', 'location'])
            ->where('company_id', $this->companyId)
            ->find($this->employeeId);

        $structures = CompensationStructure::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('structure_name')
            ->get();

        $history = app(EmployeeCompensationService::class)
            ->historyForEmployee((int) $this->companyId, (int) $this->employeeId);

        $resolved = $employee
            ? app(CompensationResolver::class)->resolveForEmployee($employee)
            : null;

        return view('livewire.employee-compensation-panel', [
            'employee' => $employee,
            'structures' => $structures,
            'history' => $history,
            'resolved' => $resolved,
        ]);
    }
}
