<?php

namespace App\Services\Compensation;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Compensation\OverrideType;
use App\Models\CompensationComponent;
use App\Models\CompensationOverride;
use App\Models\CompensationStructure;
use App\Models\CompensationStructureAssignment;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service to resolve the effective compensation structure and component values for an employee or scope.
 */
class CompensationResolver
{
    /**
     * Resolve the full compensation details for a specific employee as of a given date.
     *
     * @param Employee $employee
     * @param Carbon|null $asOf
     * @return ResolvedCompensation
     */
    public function resolveForEmployee(Employee $employee, ?Carbon $asOf = null): ResolvedCompensation
    {
        $asOf = $asOf ?? Carbon::today();
        $employee->loadMissing(['department', 'location']);

        [$structure, $structureSource] = $this->resolveStructure($employee, $asOf);
        $history = $this->resolveActiveHistory($employee, $asOf);

        $annualCtc = $history?->annual_ctc !== null ? (float) $history->annual_ctc : null;
        $monthlyGross = $history?->monthly_gross !== null ? (float) $history->monthly_gross : null;

        if (!$structure) {
            return new ResolvedCompensation(
                structureId: null,
                structureName: null,
                annualCtc: $annualCtc,
                monthlyGross: $monthlyGross,
                lines: collect(),
                structureSource: $structureSource,
            );
        }

        $lines = $this->buildBaseLines($structure);
        $lines = $this->applyOverrides($employee, $lines, $asOf);
        $lines = $this->calculateAmounts($lines, $annualCtc, $monthlyGross);

        return new ResolvedCompensation(
            structureId: $structure->id,
            structureName: $structure->structure_name,
            annualCtc: $annualCtc,
            monthlyGross: $monthlyGross,
            lines: $lines->sortBy('displayOrder')->values(),
            structureSource: $structureSource,
        );
    }

    /**
     * Preview resolved lines for a scope without an employee context.
     *
     * @param int $companyId
     * @param CompensationScopeType $scopeType
     * @param int|null $scopeId
     * @param int $structureId
     * @param float|null $annualCtc
     * @param Carbon|null $asOf
     * @return Collection<int, ResolvedComponentLine>
     */
    public function resolveForScope(
        int $companyId,
        CompensationScopeType $scopeType,
        ?int $scopeId,
        int $structureId,
        ?float $annualCtc = null,
        ?Carbon $asOf = null,
    ): Collection {
        $asOf = $asOf ?? Carbon::today();
        $structure = CompensationStructure::with('structureComponents.component')
            ->where('company_id', $companyId)
            ->findOrFail($structureId);

        $lines = $this->buildBaseLines($structure);
        $lines = $this->applyOverridesForScope($companyId, $scopeType, $scopeId, $lines, $asOf);

        return $this->calculateAmounts($lines, $annualCtc, $annualCtc ? $annualCtc / 12 : null);
    }

    /**
     * Resolve the effective structure for an employee based on hierarchy and history.
     *
     * @param Employee $employee
     * @param Carbon $asOf
     * @return array{0: ?CompensationStructure, 1: string}
     */
    private function resolveStructure(Employee $employee, Carbon $asOf): array
    {
        $history = $this->resolveActiveHistory($employee, $asOf);
        if ($history?->structure) {
            return [$history->structure, 'employee_history'];
        }

        foreach (CompensationScopeType::structureCascadeOrder() as $scopeType) {
            $scopeId = $this->scopeIdForEmployee($employee, $scopeType);
            if ($scopeType !== CompensationScopeType::COMPANY && $scopeId === null) {
                continue;
            }

            $assignment = $this->findActiveAssignment(
                $employee->company_id,
                $scopeType,
                $scopeId,
                $asOf,
            );

            if ($assignment?->structure) {
                return [$assignment->structure, $scopeType->value . '_assignment'];
            }
        }

        $default = CompensationStructure::where('company_id', $employee->company_id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        return [$default, $default ? 'company_default' : 'none'];
    }

    /**
     * Resolve the active compensation history for an employee.
     *
     * @param Employee $employee
     * @param Carbon $asOf
     * @return EmployeeCompensationHistory|null
     */
    private function resolveActiveHistory(Employee $employee, Carbon $asOf): ?EmployeeCompensationHistory
    {
        return EmployeeCompensationHistory::with('structure')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * Find the active structure assignment for a given scope.
     *
     * @param int $companyId
     * @param CompensationScopeType $scopeType
     * @param int|null $scopeId
     * @param Carbon $asOf
     * @return CompensationStructureAssignment|null
     */
    private function findActiveAssignment(
        int $companyId,
        CompensationScopeType $scopeType,
        ?int $scopeId,
        Carbon $asOf,
    ): ?CompensationStructureAssignment {
        return CompensationStructureAssignment::with('structure')
            ->where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === CompensationScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * Map scope types to employee attributes.
     *
     * @param Employee $employee
     * @param CompensationScopeType $scopeType
     * @return int|null
     */
    private function scopeIdForEmployee(Employee $employee, CompensationScopeType $scopeType): ?int
    {
        return match ($scopeType) {
            CompensationScopeType::EMPLOYEE => $employee->id,
            CompensationScopeType::DEPARTMENT => $employee->department_id,
            CompensationScopeType::LOCATION => $employee->location_id,
            CompensationScopeType::COMPANY => null,
        };
    }

    /**
     * Build base lines from the structure components.
     *
     * @param CompensationStructure $structure
     * @return Collection<int, array<string, mixed>>
     */
    private function buildBaseLines(CompensationStructure $structure): Collection
    {
        $structure->loadMissing('structureComponents.component');

        return $structure->structureComponents->mapWithKeys(function ($row) {
            $component = $row->component;
            if (!$component || !$component->is_active) {
                return [];
            }

            return [$component->id => [
                'component_id' => $component->id,
                'component_name' => $component->component_name,
                'component_type' => $component->component_type,
                'calculation_type' => $row->resolvedCalculationType(),
                'value' => $row->value ?? $component->default_value,
                'formula_expression' => $row->formula_expression,
                'is_mandatory' => $row->is_mandatory,
                'display_order' => $row->display_order ?: $component->display_order,
                'source' => 'structure',
            ]];
        });
    }

    /**
     * Apply all applicable overrides for an employee.
     *
     * @param Employee $employee
     * @param Collection<int, array<string, mixed>> $lines
     * @param Carbon $asOf
     * @return Collection<int, array<string, mixed>>
     */
    private function applyOverrides(Employee $employee, Collection $lines, Carbon $asOf): Collection
    {
        $overrides = CompensationOverride::with('component')
            ->where('company_id', $employee->company_id)
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->get();

        foreach (CompensationScopeType::overrideCascadeOrder() as $scopeType) {
            $scopeId = $this->scopeIdForEmployee($employee, $scopeType);
            $scoped = $overrides->filter(function (CompensationOverride $override) use ($scopeType, $scopeId) {
                if ($override->scope_type !== $scopeType) {
                    return false;
                }

                if ($scopeType === CompensationScopeType::COMPANY) {
                    return $override->scope_id === null;
                }

                return (int) $override->scope_id === (int) $scopeId;
            });

            $lines = $this->applyOverrideCollection($lines, $scoped, $scopeType->value);
        }

        return $lines;
    }

    /**
     * Apply overrides for a specific scope preview.
     *
     * @param int $companyId
     * @param CompensationScopeType $targetScope
     * @param int|null $targetScopeId
     * @param Collection<int, array<string, mixed>> $lines
     * @param Carbon $asOf
     * @return Collection<int, array<string, mixed>>
     */
    private function applyOverridesForScope(
        int $companyId,
        CompensationScopeType $targetScope,
        ?int $targetScopeId,
        Collection $lines,
        Carbon $asOf,
    ): Collection {
        $overrides = CompensationOverride::with('component')
            ->where('company_id', $companyId)
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->get();

        foreach (CompensationScopeType::overrideCascadeOrder() as $scopeType) {
            if ($this->scopePrecedence($scopeType) > $this->scopePrecedence($targetScope)) {
                break;
            }

            $scopeId = $scopeType === CompensationScopeType::COMPANY ? null : $targetScopeId;
            if ($scopeType !== $targetScope && $scopeType !== CompensationScopeType::COMPANY) {
                continue;
            }

            $scoped = $overrides->filter(function (CompensationOverride $override) use ($scopeType, $scopeId, $targetScope, $targetScopeId) {
                if ($override->scope_type !== $scopeType) {
                    return false;
                }

                if ($scopeType === CompensationScopeType::COMPANY) {
                    return $override->scope_id === null;
                }

                return (int) $override->scope_id === (int) $targetScopeId;
            });

            $lines = $this->applyOverrideCollection($lines, $scoped, $scopeType->value);
        }

        return $lines;
    }

    /**
     * Get the precedence level for a scope type.
     *
     * @param CompensationScopeType $scopeType
     * @return int
     */
    private function scopePrecedence(CompensationScopeType $scopeType): int
    {
        return match ($scopeType) {
            CompensationScopeType::COMPANY => 1,
            CompensationScopeType::LOCATION => 2,
            CompensationScopeType::DEPARTMENT => 3,
            CompensationScopeType::EMPLOYEE => 4,
        };
    }

    /**
     * Apply a collection of overrides to the lines.
     *
     * @param Collection<int, array<string, mixed>> $lines
     * @param Collection<int, CompensationOverride> $overrides
     * @param string $source
     * @return Collection<int, array<string, mixed>>
     */
    private function applyOverrideCollection(Collection $lines, Collection $overrides, string $source): Collection
    {
        foreach ($overrides as $override) {
            $component = $override->component;
            if (!$component) {
                continue;
            }

            $componentId = $component->id;

            if ($override->override_type === OverrideType::REMOVE) {
                $existing = $lines->get($componentId);
                if ($existing && ($existing['is_mandatory'] ?? false)) {
                    continue;
                }
                $lines->forget($componentId);
                continue;
            }

            if ($override->override_type === OverrideType::ADD) {
                $existing = $lines->get($componentId);
                if ($existing) {
                    $existing['value'] = (float) ($existing['value'] ?? 0) + (float) ($override->value ?? 0);
                    $existing['source'] = $source;
                    $lines->put($componentId, $existing);
                } else {
                    $lines->put($componentId, [
                        'component_id' => $componentId,
                        'component_name' => $component->component_name,
                        'component_type' => $component->component_type,
                        'calculation_type' => $override->calculation_type ?? $component->default_calculation_type,
                        'value' => $override->value ?? $component->default_value,
                        'formula_expression' => $override->formula_expression,
                        'is_mandatory' => false,
                        'display_order' => $component->display_order,
                        'source' => $source,
                    ]);
                }
                continue;
            }

            $lines->put($componentId, [
                'component_id' => $componentId,
                'component_name' => $component->component_name,
                'component_type' => $component->component_type,
                'calculation_type' => $override->calculation_type
                    ?? $lines->get($componentId)['calculation_type']
                    ?? $component->default_calculation_type,
                'value' => $override->value ?? $lines->get($componentId)['value'] ?? $component->default_value,
                'formula_expression' => $override->formula_expression
                    ?? $lines->get($componentId)['formula_expression'] ?? null,
                'is_mandatory' => $lines->get($componentId)['is_mandatory'] ?? false,
                'display_order' => $lines->get($componentId)['display_order'] ?? $component->display_order,
                'source' => $source,
            ]);
        }

        return $lines;
    }

    /**
     * Calculate final amounts for each component line.
     *
     * @param Collection<int, array<string, mixed>> $lines
     * @param float|null $annualCtc
     * @param float|null $monthlyGross
     * @return Collection<int, ResolvedComponentLine>
     */
    private function calculateAmounts(Collection $lines, ?float $annualCtc, ?float $monthlyGross): Collection
    {
        $monthlyCtc = $annualCtc ? $annualCtc / 12 : null;

        $basicAmount = $this->resolveBasicAmount($lines, $monthlyCtc, $monthlyGross);

        return $lines->map(function (array $line) use ($basicAmount, $monthlyCtc) {
            $amount = match ($line['calculation_type']) {
                CalculationType::FIXED => (float) ($line['value'] ?? 0),
                CalculationType::PERCENT_BASIC => $basicAmount * ((float) ($line['value'] ?? 0) / 100),
                CalculationType::PERCENT_CTC => ($monthlyCtc ?? 0) * ((float) ($line['value'] ?? 0) / 100),
                CalculationType::FORMULA => 0,
            };

            return new ResolvedComponentLine(
                componentId: $line['component_id'],
                componentName: $line['component_name'],
                componentType: $line['component_type'],
                calculationType: $line['calculation_type'],
                value: $line['value'] !== null ? (float) $line['value'] : null,
                formulaExpression: $line['formula_expression'],
                isMandatory: (bool) $line['is_mandatory'],
                displayOrder: (int) $line['display_order'],
                source: $line['source'],
                monthlyAmount: round($amount, 2),
            );
        });
    }

    /**
     * Resolve the amount to be used as 'Basic' for percentage calculations.
     *
     * @param Collection<int, array<string, mixed>> $lines
     * @param float|null $monthlyCtc
     * @param float|null $monthlyGross
     * @return float
     */
    private function resolveBasicAmount(Collection $lines, ?float $monthlyCtc, ?float $monthlyGross): float
    {
        $basicLine = $lines->first(function (array $line) {
            return strcasecmp($line['component_name'], 'Basic') === 0
                && $line['calculation_type'] === CalculationType::FIXED;
        });

        if ($basicLine) {
            return (float) ($basicLine['value'] ?? 0);
        }

        if ($monthlyGross) {
            return $monthlyGross;
        }

        return $monthlyCtc ?? 0;
    }

    /**
     * Get the inheritance chain of structures for an employee.
     *
     * @param Employee $employee
     * @param Carbon|null $asOf
     * @return array<string, ?array{structure_name: string, source: string}>
     */
    public function structureInheritanceChain(Employee $employee, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::today();
        $chain = [];

        foreach ([
            CompensationScopeType::COMPANY,
            CompensationScopeType::LOCATION,
            CompensationScopeType::DEPARTMENT,
            CompensationScopeType::EMPLOYEE,
        ] as $scopeType) {
            $scopeId = $this->scopeIdForEmployee($employee, $scopeType);
            if ($scopeType !== CompensationScopeType::COMPANY && !$scopeId) {
                $chain[$scopeType->value] = null;
                continue;
            }

            $assignment = $this->findActiveAssignment($employee->company_id, $scopeType, $scopeId, $asOf);
            $chain[$scopeType->value] = $assignment?->structure
                ? ['structure_name' => $assignment->structure->structure_name, 'source' => $scopeType->value]
                : null;
        }

        $history = $this->resolveActiveHistory($employee, $asOf);
        if ($history?->structure) {
            $chain['employee_history'] = [
                'structure_name' => $history->structure->structure_name,
                'source' => 'employee_history',
            ];
        }

        return $chain;
    }
}
