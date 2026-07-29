<?php

namespace App\Services\Compensation;

use App\Models\CompensationComponent;
use App\Models\CompensationStructure;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompensationStructureImportService
{
    public function __construct(
        private readonly CompensationStructureService $structureService,
    ) {
    }

    /**
     * @param int $companyId
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $initialErrors
     * @return array{total_groups:int,created:int,failed:int,errors:array<int,string>}
     */
    public function import(int $companyId, array $rows, array $initialErrors = []): array
    {
        $errors = $initialErrors;
        $created = 0;
        $failed = 0;

        $componentMap = $this->buildComponentNameMap($companyId);
        $grouped = collect($rows)
            ->groupBy(fn (array $row): string => mb_strtolower(trim((string) ($row['structure_name'] ?? ''))));

        foreach ($grouped as $normalizedStructureName => $groupRows) {
            $rowsForStructure = $groupRows->values();
            $firstRow = $rowsForStructure->first() ?? [];
            $structureName = trim((string) ($firstRow['structure_name'] ?? $normalizedStructureName));
            $firstRowNumber = (int) ($firstRow['_row_number'] ?? 0);

            if ($this->structureExists($companyId, $structureName)) {
                $failed++;
                $errors[] = $this->formatError($firstRowNumber, "Structure '{$structureName}' already exists.");
                continue;
            }

            $payload = [
                'structure_name' => $structureName,
                'effective_from' => $this->nullableTrim($firstRow['effective_from'] ?? null),
                'effective_to' => $this->nullableTrim($firstRow['effective_to'] ?? null),
                'is_active' => true,
                'is_default' => $this->toBool($firstRow['is_default'] ?? false),
            ];

            $componentRows = [];
            $groupHasRowError = false;

            foreach ($rowsForStructure as $index => $row) {
                $rowNumber = (int) ($row['_row_number'] ?? 0);
                $componentName = mb_strtolower(trim((string) ($row['component_name'] ?? '')));

                if ($componentName === '' || !isset($componentMap[$componentName])) {
                    $failed++;
                    $groupHasRowError = true;
                    $errors[] = $this->formatError($rowNumber, "Component '{$row['component_name']}' was not found or inactive.");
                    break;
                }

                $componentRows[] = [
                    'component_id' => $componentMap[$componentName],
                    'value' => $this->nullableNumber($row['value'] ?? null),
                    'calculation_type' => $this->nullableTrim($row['calculation_type'] ?? null),
                    'formula_expression' => null,
                    'is_mandatory' => false,
                    'display_order' => $this->toDisplayOrder($row['display_order'] ?? null, $index + 1),
                ];
            }

            if ($groupHasRowError) {
                continue;
            }

            try {
                $this->structureService->create($companyId, $payload, $componentRows);
                $created++;
            } catch (ValidationException $e) {
                $failed++;
                $message = collect($e->errors())->flatten()->first() ?? 'Validation failed for structure import.';
                $errors[] = $this->formatError($firstRowNumber, (string) $message);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $this->formatError($firstRowNumber, $e->getMessage());
            }
        }

        return [
            'total_groups' => $grouped->count(),
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function buildComponentNameMap(int $companyId): array
    {
        /** @var Collection<int, CompensationComponent> $components */
        $components = CompensationComponent::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $map = [];
        foreach ($components as $component) {
            $map[mb_strtolower(trim($component->component_name))] = $component->id;
        }

        return $map;
    }

    private function structureExists(int $companyId, string $structureName): bool
    {
        return CompensationStructure::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(structure_name) = ?', [mb_strtolower($structureName)])
            ->exists();
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));
        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (float) $value;
    }

    private function toDisplayOrder(mixed $value, int $fallback): int
    {
        if ($value === null || trim((string) $value) === '') {
            return $fallback;
        }

        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function formatError(int $rowNumber, string $message): string
    {
        if ($rowNumber > 0) {
            return "Row {$rowNumber}: {$message}";
        }

        return $message;
    }
}
