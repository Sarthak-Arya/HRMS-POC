<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $rowNumber = 0;
    private int $importedCount = 0;
    private int $failedCount = 0;
    private array $errors = [];

    private int $companyId;
    private int $headingRow;

    /** @var array<string,int> */
    private array $departmentCache = [];
    /** @var array<string,int> */
    private array $designationCache = [];
    /** @var array<string,int> */
    private array $locationCache = [];

    public function __construct(int $companyId, int $headingRow = 1)
    {
        // Use normalized headings (snake_case) for robustness.
        HeadingRowFormatter::default('slug');
        $this->companyId = $companyId;
        $this->headingRow = $headingRow;
        // Make error row numbers match the source sheet (first data row is headingRow + 1).
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

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'failed' => $this->failedCount,
            'total_processed' => $this->rowNumber,
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;
            $data = $this->normalizeRow($row->toArray());

            // Skip fully empty rows.
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
                $ok = $this->insertEmployee($data);
                if ($ok) {
                    $this->importedCount++;
                } else {
                    $this->failedCount++;
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

    private function addError(int $rowNumber, string $field, string $message): void
    {
        $this->errors[] = [
            'row' => $rowNumber,
            'field' => $field,
            'message' => $message,
        ];
    }

    /**
     * Accept both the old template headings and more "free-form" headings.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeRow(array $row): array
    {
        // If the sheet used the old provided template, keys may come through as
        // e.g. "first_name", but also might be "first_name_" etc depending on the formatter.
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

        // Keep original for any direct access fallback.
        return array_merge($row, $mapped);
    }

    private function normalizeGender(mixed $value): string
    {
        $raw = Str::upper(trim((string) $value));
        if ($raw === '') {
            return 'O';
        }
        if (in_array($raw, ['M', 'MALE'], true)) return 'M';
        if (in_array($raw, ['F', 'FEMALE'], true)) return 'F';
        if (in_array($raw, ['O', 'OTHER'], true)) return 'O';
        return 'O';
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensureDepartmentId(?string $name): int
    {
        $departmentName = trim((string) $name);
        if ($departmentName === '') $departmentName = 'General';
        $key = Str::lower($departmentName);

        if (isset($this->departmentCache[$key])) {
            return $this->departmentCache[$key];
        }

        $department = Department::firstOrCreate([
            'company_id' => $this->companyId,
            'department_name' => $departmentName,
        ]);

        return $this->departmentCache[$key] = $department->id;
    }

    private function ensureDesignationId(?string $name): int
    {
        $designationName = trim((string) $name);
        if ($designationName === '') $designationName = 'Staff';
        $key = Str::lower($designationName);

        if (isset($this->designationCache[$key])) {
            return $this->designationCache[$key];
        }

        $designation = Designation::firstOrCreate([
            'company_id' => $this->companyId,
            'designation_name' => $designationName,
        ]);

        return $this->designationCache[$key] = $designation->id;
    }

    private function ensureLocationId(?string $name): int
    {
        $locationName = trim((string) $name);
        if ($locationName === '') $locationName = 'Default';
        $key = Str::lower($locationName);

        if (isset($this->locationCache[$key])) {
            return $this->locationCache[$key];
        }

        $existing = Location::where('company_id', $this->companyId)
            ->whereRaw('LOWER(location_name) = ?', [$key])
            ->first();
        if ($existing) {
            return $this->locationCache[$key] = $existing->id;
        }

        $base = preg_replace('/[^A-Z0-9]/', '', Str::upper($locationName));
        $prefix = Str::substr($base ?: 'LOC', 0, 6);
        $locationCode = $prefix . Str::upper(Str::random(4));

        $location = Location::create([
            'company_id' => $this->companyId,
            'location_name' => $locationName,
            'location_code' => $locationCode,
            'location_address' => '',
            'location_city' => '',
            'location_state' => '',
            'location_pincode' => '',
            'location_country' => '',
            'location_phone' => '',
            'location_email' => '',
        ]);

        return $this->locationCache[$key] = $location->id;
    }

    private function generateEmployeeCode(): string
    {
        // <= 20 chars
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'EMP' . Str::upper(Str::random(10)); // 13 chars
            if (!Employee::where('employee_code', $candidate)->exists()) {
                return $candidate;
            }
        }
        return 'EMP' . time();
    }

    private function isFilled(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    /**
     * @param array<string,mixed> $row
     */
    private function insertEmployee(array $row): bool
    {
        $employeeCode = trim((string) ($row['employee_code'] ?? ''));
        if ($employeeCode === '') {
            $employeeCode = $this->generateEmployeeCode();
        }

        $existingEmployee = Employee::where('employee_code', $employeeCode)->first();
        if ($existingEmployee && (int) $existingEmployee->company_id !== (int) $this->companyId) {
            $this->addError($this->rowNumber, 'employee_code', "Employee code '$employeeCode' belongs to a different company (skipped).");
            return false;
        }

        $employeeName = trim((string) ($row['employee_name'] ?? ''));
        if ($employeeName === '') {
            $parts = array_filter([
                trim((string) ($row['first_name'] ?? '')),
                trim((string) ($row['middle_name'] ?? '')),
                trim((string) ($row['last_name'] ?? '')),
            ]);
            $employeeName = trim(implode(' ', $parts));
        }
        if ($employeeName === '') {
            $employeeName = 'Employee ' . $employeeCode;
        }

        $departmentId = $this->isFilled($row['department'] ?? null)
            ? $this->ensureDepartmentId($row['department'] ?? null)
            : (int) ($existingEmployee?->department_id ?? $this->ensureDepartmentId(null));

        $designationId = $this->isFilled($row['designation'] ?? null)
            ? $this->ensureDesignationId($row['designation'] ?? null)
            : (int) ($existingEmployee?->designation_id ?? $this->ensureDesignationId(null));

        $locationId = $this->isFilled($row['location'] ?? null)
            ? $this->ensureLocationId($row['location'] ?? null)
            : (int) ($existingEmployee?->location_id ?? $this->ensureLocationId(null));

        $gender = $existingEmployee ? ($existingEmployee->gender ?? 'O') : 'O';
        if ($this->isFilled($row['gender'] ?? null)) {
            $gender = $this->normalizeGender($row['gender'] ?? null);
        }

        $dob = $existingEmployee?->dob;
        if ($this->isFilled($row['dob'] ?? null)) {
            $dob = $this->parseExcelDate($row['dob'] ?? null);
        }
        $doj = $existingEmployee?->doj;
        if ($this->isFilled($row['doj'] ?? null)) {
            $doj = $this->parseExcelDate($row['doj'] ?? null);
        }
        $dol = $existingEmployee?->dol;
        if ($this->isFilled($row['dol'] ?? null)) {
            $dol = $this->parseExcelDate($row['dol'] ?? null);
        }

        $payload = [
            'company_id' => $this->companyId,
            'employee_code' => $employeeCode,
            'employee_name' => $employeeName ?: ($existingEmployee?->employee_name ?? ('Employee ' . $employeeCode)),
            'father_name' => $this->isFilled($row['father_name'] ?? null)
                ? (trim((string) ($row['father_name'] ?? '')) ?: null)
                : ($existingEmployee?->father_name),
            'gender' => $gender,
            'dob' => $dob,
            'doj' => $doj,
            'dol' => $dol,
            'pf_no' => $this->isFilled($row['pf_no'] ?? null)
                ? (trim((string) ($row['pf_no'] ?? '')) ?: null)
                : ($existingEmployee?->pf_no),
            'esi_no' => $this->isFilled($row['esi_no'] ?? null)
                ? (trim((string) ($row['esi_no'] ?? '')) ?: null)
                : ($existingEmployee?->esi_no),
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'location_id' => $locationId,
            'bank_name' => $this->isFilled($row['bank_name'] ?? null)
                ? (trim((string) ($row['bank_name'] ?? '')) ?: null)
                : ($existingEmployee?->bank_name),
            'bank_account_no' => $this->isFilled($row['bank_account_no'] ?? null)
                ? (trim((string) ($row['bank_account_no'] ?? '')) ?: null)
                : ($existingEmployee?->bank_account_no),
            'bank_ifsc_code' => $this->isFilled($row['bank_ifsc_code'] ?? null)
                ? (trim((string) ($row['bank_ifsc_code'] ?? '')) ?: null)
                : ($existingEmployee?->bank_ifsc_code),
        ];

        try {
            if ($existingEmployee) {
                $existingEmployee->update($payload);
            } else {
                Employee::create($payload);
            }
            return true;
        } catch (QueryException $e) {
            // Keep import going; record a user-friendly message.
            $sqlState = $e->getCode();
            $errorCode = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
            $message = 'Database error while importing employee.';

            if ($sqlState === '23000' && $errorCode == 1062) {
                $message = "Duplicate employee code '$employeeCode'.";
            } elseif ($sqlState === '23000' && $errorCode == 1452) {
                $message = 'Invalid department/designation/location reference.';
            }

            Log::error('Employee import SQL error', [
                'row' => $this->rowNumber,
                'company_id' => $this->companyId,
                'sqlstate' => $sqlState,
                'error_code' => $errorCode,
                'exception_message' => $e->getMessage(),
                'employee_code' => $employeeCode,
            ]);

            $this->addError($this->rowNumber, 'database', $message);
            return false;
        }
    }
}
