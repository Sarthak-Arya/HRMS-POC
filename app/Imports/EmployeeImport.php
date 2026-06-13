<?php

namespace App\Imports;

use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

/**
 * Excel import class for employees.
 * Handles reading employee data from Excel collections and upserting into the database.
 */
class EmployeeImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /** @var int Current row number being processed */
    private int $rowNumber = 0;

    /** @var int Number of employees successfully imported */
    private int $importedCount = 0;

    /** @var int Number of rows that failed to import */
    private int $failedCount = 0;

    /** @var array<int, array<string, mixed>> List of errors encountered during import */
    private array $errors = [];

    /** @var int The ID of the company to import employees for */
    private int $companyId;

    /** @var int The row number that contains the headers */
    private int $headingRow;

    /** @var EmployeeService Service for employee operations */
    private EmployeeService $employeeService;

    /**
     * Create a new import instance.
     *
     * @param int $companyId The ID of the company.
     * @param int $headingRow The row number for headers.
     * @param EmployeeService|null $employeeService Optional service instance.
     */
    public function __construct(int $companyId, int $headingRow = 1, ?EmployeeService $employeeService = null)
    {
        HeadingRowFormatter::default('slug');
        $this->companyId = $companyId;
        $this->headingRow = $headingRow;
        $this->rowNumber = $headingRow;
        $this->employeeService = $employeeService ?? app(EmployeeService::class);
    }

    /**
     * Specify the heading row number.
     *
     * @return int
     */
    public function headingRow(): int
    {
        return $this->headingRow;
    }

    /**
     * Specify the chunk size for reading the Excel file.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * Get the errors encountered during import.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get stats about the import process.
     *
     * @return array{imported: int, failed: int, total_processed: int}
     */
    public function getImportStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'failed' => $this->failedCount,
            'total_processed' => $this->rowNumber,
        ];
    }

    /**
     * Process a collection of rows from the Excel file.
     *
     * @param Collection $rows The collection of rows.
     * @return void
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;
            $data = $this->normalizeRow($row->toArray());

            $hasAnyValue = false;
            foreach ($data as $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    $hasAnyValue = true;
                    break;
                }
            }
            if (!$hasAnyValue) {
                continue;
            }

            try {
                $employeeCode = trim((string) ($data['employee_code'] ?? ''));
                $existing = $employeeCode !== ''
                    ? Employee::where('employee_code', $employeeCode)->first()
                    : null;

                $result = $this->employeeService->upsertFromImportRow($this->companyId, $data, $existing);

                if ($result['success']) {
                    $this->importedCount++;
                } else {
                    $this->failedCount++;
                    $this->addError(
                        $this->rowNumber,
                        $result['field'] ?? 'import',
                        $result['error'] ?? 'Import failed.'
                    );
                }
            } catch (\Throwable $e) {
                $this->failedCount++;
                $this->addError($this->rowNumber, 'import', $e->getMessage());
                Log::error('Employee import row failed', [
                    'row' => $this->rowNumber,
                    'company_id' => $this->companyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Add an error to the error list.
     *
     * @param int $rowNumber The row number where error occurred.
     * @param string $field The field that caused the error.
     * @param string $message The error message.
     * @return void
     */
    private function addError(int $rowNumber, string $field, string $message): void
    {
        $this->errors[] = [
            'row' => $rowNumber,
            'field' => $field,
            'message' => $message,
        ];
    }

    /**
     * Normalize a row by mapping synonyms to target keys.
     *
     * @param array<string,mixed> $row The raw row data.
     * @return array<string,mixed> The normalized row data.
     */
    private function normalizeRow(array $row): array
    {
        $mapped = [];

        $synonyms = [
            'employee_code' => ['employee_code', 'emp_code', 'empno', 'emp_no', 'employee_no', 'company_code', 'employee_company_code'],
            'employee_name' => ['employee_name', 'name', 'full_name', 'employee_full_name'],
            'first_name' => ['first_name', 'firstname', 'first'],
            'middle_name' => ['middle_name', 'middlename', 'middle'],
            'last_name' => ['last_name', 'lastname', 'last', 'surname'],
            'father_name' => ['father_name', 'father', 'fathers_name'],
            'gender' => ['gender', 'sex', 'gender_m_f_o'],
            'dob' => ['dob', 'date_of_birth', 'birth_date', 'date_of_birth_yyyy_mm_dd'],
            'doj' => ['doj', 'joining_date', 'date_of_joining', 'join_date', 'joining_date_yyyy_mm_dd'],
            'dol' => ['dol', 'leaving_date', 'date_of_leaving', 'leave_date', 'leaving_date_yyyy_mm_dd'],
            'department' => ['department', 'dept', 'department_name'],
            'designation' => ['designation', 'role', 'designation_name'],
            'location' => ['location', 'location_name', 'site', 'branch', 'deployment_site'],
            'pf_no' => ['pf_no', 'pf_number', 'pf'],
            'esi_no' => ['esi_no', 'esi_number', 'esi'],
            'bank_name' => ['bank_name', 'bank'],
            'bank_account_no' => ['bank_account_no', 'account_no', 'account_number', 'bank_account'],
            'bank_ifsc_code' => ['bank_ifsc_code', 'ifsc', 'ifsc_code'],
        ];

        foreach ($synonyms as $targetKey => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                    $mapped[$targetKey] = $row[$key];
                    break;
                }
            }
        }

        return array_merge($row, $mapped);
    }
}
