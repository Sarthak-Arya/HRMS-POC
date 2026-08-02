<?php

namespace App\Services\Payroll;

use App\Enums\Compensation\ComponentType;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Models\CompensationComponent;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Services\Observability\DomainTelemetry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PayrollAdjustmentService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {}

    /**
     * @return Collection<int, CompensationComponent>
     */
    public function listAdjustmentComponents(int $companyId): Collection
    {
        return CompensationComponent::query()
            ->where('company_id', $companyId)
            ->where('is_payroll_adjustment', true)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{saved: int, skipped: int}
     */
    public function createBulk(PayrollRun $run, array $rows): array
    {
        app(PayrollRunLifecycle::class)->assertRunEditable($run);

        $adjustmentComponents = $this->listAdjustmentComponents((int) $run->company_id)
            ->keyBy('id');

        if ($adjustmentComponents->isEmpty()) {
            throw ValidationException::withMessages([
                'adjustmentRows' => 'Create payroll adjustment components in Compensation first.',
            ]);
        }

        $saved = 0;
        $skipped = 0;

        DB::transaction(function () use ($run, $rows, $adjustmentComponents, &$saved, &$skipped) {
            foreach ($rows as $index => $row) {
                $employeeId = trim((string) ($row['employee_id'] ?? ''));
                $componentId = trim((string) ($row['component_id'] ?? ''));
                $amount = trim((string) ($row['amount'] ?? ''));
                $remarks = trim((string) ($row['remarks'] ?? ''));

                if ($employeeId === '' && $componentId === '' && $amount === '' && $remarks === '') {
                    $skipped++;

                    continue;
                }

                $validated = Validator::make(
                    [
                        'employee_id' => $employeeId !== '' ? (int) $employeeId : null,
                        'component_id' => $componentId !== '' ? (int) $componentId : null,
                        'amount' => $amount !== '' ? $amount : null,
                        'remarks' => $remarks !== '' ? $remarks : null,
                    ],
                    [
                        'employee_id' => 'required|exists:employees,id',
                        'component_id' => 'required|exists:compensation_components,id',
                        'amount' => 'required|numeric|min:0.01',
                        'remarks' => 'nullable|string|max:500',
                    ],
                    [],
                    [
                        'employee_id' => "row ".($index + 1)." employee",
                        'component_id' => "row ".($index + 1)." adjustment",
                        'amount' => "row ".($index + 1)." amount",
                    ]
                )->validate();

                /** @var CompensationComponent|null $component */
                $component = $adjustmentComponents->get((int) $validated['component_id']);
                if ($component === null) {
                    throw ValidationException::withMessages([
                        "adjustmentRows.{$index}.component_id" => 'Select a valid payroll adjustment component.',
                    ]);
                }

                PayrollAdjustment::create([
                    'employee_id' => (int) $validated['employee_id'],
                    'payroll_run_id' => $run->id,
                    'component_id' => $component->id,
                    'adjustment_type' => $this->adjustmentTypeForComponent($component),
                    'amount' => $validated['amount'],
                    'remarks' => $validated['remarks'],
                    'created_by' => Auth::id(),
                ]);

                $saved++;
            }
        });

        if ($saved === 0) {
            throw ValidationException::withMessages([
                'adjustmentRows' => 'Add at least one complete adjustment row before saving.',
            ]);
        }

        $this->telemetry->emit('compensation.adjustment.applied', 'business', 'success', [
            'company.id' => (int) $run->company_id,
            'payroll.run_id' => $run->id,
            'processed_count' => $saved,
        ]);

        return ['saved' => $saved, 'skipped' => $skipped];
    }

    /**
     * @return array{rowData: list<array<string, mixed>>, components: list<array<string, mixed>>, editable: bool}
     */
    public function buildMatrix(PayrollRun $run, ?int $departmentId = null, ?int $designationId = null): array
    {
        $companyId = (int) $run->company_id;
        $components = $this->listAdjustmentComponents($companyId);

        $employees = app(PayrollReadinessService::class)->eligibleEmployees(
            $run,
            $departmentId,
            $designationId,
        )->load('department');

        $adjustments = PayrollAdjustment::query()
            ->where('payroll_run_id', $run->id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy(fn (PayrollAdjustment $adjustment) => $adjustment->employee_id.'_'.$adjustment->component_id);

        $rowData = [];

        foreach ($employees as $employee) {
            $row = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->employee_name,
                'department' => $employee->department?->department_name ?? '',
            ];

            foreach ($components as $component) {
                $key = 'adj_'.$component->id;
                $adjustment = $adjustments->get($employee->id.'_'.$component->id);
                $row[$key] = $adjustment !== null ? (float) $adjustment->amount : null;
            }

            $rowData[] = $row;
        }

        return [
            'rowData' => $rowData,
            'components' => $components->map(fn (CompensationComponent $component) => [
                'id' => $component->id,
                'name' => $component->component_name,
                'type' => $component->component_type->value,
            ])->values()->all(),
            'editable' => ! $run->isLocked(),
        ];
    }

    /**
     * @param list<array{employee_id: int|string, component_id: int|string, amount: mixed}> $changes
     * @return array{saved: int, deleted: int}
     */
    public function syncMatrix(PayrollRun $run, array $changes): array
    {
        app(PayrollRunLifecycle::class)->assertRunEditable($run);

        $adjustmentComponents = $this->listAdjustmentComponents((int) $run->company_id)
            ->keyBy('id');

        if ($adjustmentComponents->isEmpty()) {
            throw ValidationException::withMessages([
                'adjustmentMatrix' => 'Create payroll adjustment components in Compensation first.',
            ]);
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'adjustmentMatrix' => 'No changes to save.',
            ]);
        }

        $saved = 0;
        $deleted = 0;

        DB::transaction(function () use ($run, $changes, $adjustmentComponents, &$saved, &$deleted) {
            foreach ($changes as $index => $change) {
                $validated = Validator::make(
                    [
                        'employee_id' => $change['employee_id'] ?? null,
                        'component_id' => $change['component_id'] ?? null,
                        'amount' => array_key_exists('amount', $change) ? $change['amount'] : null,
                    ],
                    [
                        'employee_id' => 'required|integer|exists:employees,id',
                        'component_id' => 'required|integer|exists:compensation_components,id',
                        'amount' => 'nullable|numeric|min:0',
                    ],
                    [],
                    [
                        'employee_id' => 'row '.($index + 1).' employee',
                        'component_id' => 'row '.($index + 1).' adjustment',
                        'amount' => 'row '.($index + 1).' amount',
                    ]
                )->validate();

                /** @var Employee $employee */
                $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
                if ((int) $employee->company_id !== (int) $run->company_id) {
                    throw ValidationException::withMessages([
                        "changes.{$index}.employee_id" => 'Employee does not belong to this company.',
                    ]);
                }

                /** @var CompensationComponent|null $component */
                $component = $adjustmentComponents->get((int) $validated['component_id']);
                if ($component === null) {
                    throw ValidationException::withMessages([
                        "changes.{$index}.component_id" => 'Select a valid payroll adjustment component.',
                    ]);
                }

                $amount = $validated['amount'];
                $existing = PayrollAdjustment::query()
                    ->where('payroll_run_id', $run->id)
                    ->where('employee_id', $employee->id)
                    ->where('component_id', $component->id)
                    ->first();

                if ($amount === null || $amount === '' || (float) $amount <= 0) {
                    if ($existing !== null) {
                        $existing->delete();
                        $deleted++;
                    }

                    continue;
                }

                if ($existing !== null) {
                    $existing->update([
                        'amount' => $amount,
                        'adjustment_type' => $this->adjustmentTypeForComponent($component),
                    ]);
                } else {
                    PayrollAdjustment::create([
                        'employee_id' => $employee->id,
                        'payroll_run_id' => $run->id,
                        'component_id' => $component->id,
                        'adjustment_type' => $this->adjustmentTypeForComponent($component),
                        'amount' => $amount,
                        'remarks' => null,
                        'created_by' => Auth::id(),
                    ]);
                }

                $saved++;
            }
        });

        $this->telemetry->emit('compensation.adjustment.applied', 'business', 'success', [
            'company.id' => (int) $run->company_id,
            'payroll.run_id' => $run->id,
            'processed_count' => $saved,
        ]);

        return ['saved' => $saved, 'deleted' => $deleted];
    }

    public function adjustmentTypeForComponent(CompensationComponent $component): PayrollAdjustmentType
    {
        return $component->component_type === ComponentType::DEDUCTION
            ? PayrollAdjustmentType::DEDUCTION
            : PayrollAdjustmentType::ADDITION;
    }
}
