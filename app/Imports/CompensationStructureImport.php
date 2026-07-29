<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class CompensationStructureImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /** @var int */
    private int $headingRow;

    /** @var int */
    private int $rowNumber = 1;

    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(int $headingRow = 1)
    {
        HeadingRowFormatter::default('slug');
        $this->headingRow = $headingRow;
        $this->rowNumber = $headingRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;
            $normalized = $this->normalizeRow($row->toArray());

            if ($this->isEmptyRow($normalized)) {
                continue;
            }

            $required = ['structure_name', 'component_name'];
            foreach ($required as $field) {
                if (!isset($normalized[$field]) || trim((string) $normalized[$field]) === '') {
                    $this->errors[] = "Row {$this->rowNumber}: Missing required field {$field}.";
                    continue 2;
                }
            }

            $normalized['_row_number'] = $this->rowNumber;
            $this->rows[] = $normalized;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $mapped = [];
        $synonyms = [
            'structure_name' => ['structure_name', 'structure', 'salary_structure'],
            'effective_from' => ['effective_from', 'start_date', 'from_date'],
            'effective_to' => ['effective_to', 'end_date', 'to_date'],
            'is_default' => ['is_default', 'default', 'default_structure'],
            'component_name' => ['component_name', 'component'],
            'calculation_type' => ['calculation_type', 'calc_type'],
            'value' => ['value', 'amount', 'component_value'],
            'display_order' => ['display_order', 'order', 'sort_order'],
        ];

        foreach ($synonyms as $target => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row) && trim((string) ($row[$key] ?? '')) !== '') {
                    $mapped[$target] = $row[$key];
                    break;
                }
            }
        }

        return array_merge($row, $mapped);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
