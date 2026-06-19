<?php

namespace App\Services\Employee;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Service class for managing employee-related operations.
 * Handles creation, updates, searches, and imports of employee data.
 */
class EmployeeService
{
    /** @var array<string,int> Cache for department IDs */
    private array $departmentCache = [];

    /** @var array<string,int> Cache for designation IDs */
    private array $designationCache = [];

    /** @var array<string,int> Cache for location IDs */
    private array $locationCache = [];

    /**
     * Create or update an employee from form data.
     *
     * @param int $companyId The ID of the company the employee belongs to.
     * @param array<string, mixed> $data The form data for the employee.
     * @param int|null $employeeId The ID of the employee to update, or null for a new employee.
     * @return array<string, mixed> Result of the operation including the action performed and the employee model.
     */
    public function createFromForm(int $companyId, array $data, ?int $employeeId = null): array
    {
        $validated = $this->validateFormData($companyId, $data, $employeeId);

        $department = $this->ensureDepartment($companyId, $validated['department']);
        $designation = $this->ensureDesignation($companyId, $validated['designation']);
        $location = $this->ensureLocation($companyId, $validated['location']);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? '',
            $validated['last_name'],
        ])));

        $payload = [
            'company_id' => $companyId,
            'employee_code' => $validated['employee_code'],
            'employee_name' => $fullName,
            'father_name' => $validated['father_name'],
            'gender' => strtoupper(substr($validated['gender'], 0, 1)),
            'dob' => $validated['dob'] ?? null,
            'doj' => $validated['joining_date'] ?? null,
            'dol' => $validated['leaving_date'] ?? null,
            'esi_no' => $validated['esi_no'] ?? null,
            'pf_no' => $validated['pf_no'] ?? null,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'location_id' => $location->id,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_no' => $validated['account_no'] ?? null,
            'bank_ifsc_code' => $validated['ifsc_code'] ?? null,
        ];

        if ($employeeId) {
            $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);
            $employee->update($payload);

            return ['action' => 'updated', 'employee' => $employee->fresh(['department', 'designation', 'location'])];
        }

        $employee = Employee::create($payload);

        return ['action' => 'created', 'employee' => $employee->load(['department', 'designation', 'location'])];
    }

    /**
     * Upsert an employee from an import row.
     *
     * @param int $companyId The ID of the company.
     * @param array<string,mixed> $row The row data from the import.
     * @param Employee|null $existingEmployee Existing employee instance if known.
     * @return array{success: bool, error?: string, field?: string} Result of the upsert operation.
     */
    public function upsertFromImportRow(int $companyId, array $row, ?Employee $existingEmployee = null): array
    {
        $employeeCode = trim((string) ($row['employee_code'] ?? ''));
        if ($employeeCode === '') {
            $employeeCode = $existingEmployee
                ? $existingEmployee->employee_code
                : $this->generateEmployeeCode();
        }

        if (!$existingEmployee) {
            $existingEmployee = Employee::where('employee_code', $employeeCode)->first();
        }

        if ($existingEmployee && (int) $existingEmployee->company_id !== $companyId) {
            return [
                'success' => false,
                'field' => 'employee_code',
                'error' => "Employee code '$employeeCode' belongs to a different company (skipped).",
            ];
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
            ? $this->ensureDepartmentId($companyId, $row['department'] ?? null)
            : (int) ($existingEmployee?->department_id ?? $this->ensureDepartmentId($companyId, null));

        $designationId = $this->isFilled($row['designation'] ?? null)
            ? $this->ensureDesignationId($companyId, $row['designation'] ?? null)
            : (int) ($existingEmployee?->designation_id ?? $this->ensureDesignationId($companyId, null));

        $locationId = $this->isFilled($row['location'] ?? null)
            ? $this->ensureLocationId($companyId, $row['location'] ?? null)
            : (int) ($existingEmployee?->location_id ?? $this->ensureLocationId($companyId, null));

        $gender = $existingEmployee ? ($existingEmployee->gender ?? 'O') : 'O';
        if ($this->isFilled($row['gender'] ?? null)) {
            $gender = $this->normalizeGender($row['gender'] ?? null);
        }

        $dob = $existingEmployee?->dob;
        if ($this->isFilled($row['dob'] ?? null)) {
            $dob = $this->parseDate($row['dob'] ?? null);
        }
        $doj = $existingEmployee?->doj;
        if ($this->isFilled($row['doj'] ?? null)) {
            $doj = $this->parseDate($row['doj'] ?? null);
        }
        $dol = $existingEmployee?->dol;
        if ($this->isFilled($row['dol'] ?? null)) {
            $dol = $this->parseDate($row['dol'] ?? null);
        }

        $payload = [
            'company_id' => $companyId,
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

            return ['success' => true];
        } catch (QueryException $e) {
            $sqlState = $e->getCode();
            $errorCode = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
            $message = 'Database error while importing employee.';

            if ($sqlState === '23000' && $errorCode == 1062) {
                $message = "Duplicate employee code '$employeeCode'.";
            } elseif ($sqlState === '23000' && $errorCode == 1452) {
                $message = 'Invalid department/designation/location reference.';
            }

            Log::error('Employee upsert SQL error', [
                'company_id' => $companyId,
                'sqlstate' => $sqlState,
                'error_code' => $errorCode,
                'exception_message' => $e->getMessage(),
                'employee_code' => $employeeCode,
            ]);

            return ['success' => false, 'field' => 'database', 'error' => $message];
        }
    }

    /**
     * Upsert multiple employees from a list of rows.
     *
     * @param int $companyId The ID of the company.
     * @param array<int, array<string,mixed>> $employees List of employee data rows.
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>} Stats of the operation.
     */
    public function upsertMany(int $companyId, array $employees): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($employees as $index => $row) {
            $normalized = $this->normalizeAgentRow($row);
            $code = trim((string) ($normalized['employee_code'] ?? ''));
            $existing = $code !== '' ? Employee::where('employee_code', $code)->first() : null;

            $result = $this->upsertFromImportRow($companyId, $normalized, $existing);

            if ($result['success']) {
                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            } else {
                $failed++;
                $errors[$index] = $result['error'] ?? 'Unknown error';
            }
        }

        return compact('created', 'updated', 'failed', 'errors');
    }

    /**
     * Search for employees based on filters.
     *
     * @param int $companyId The ID of the company.
     * @param array<string,mixed> $filters Search filters (search, department, designation, location, status).
     * @param int $limit Maximum number of results to return.
     * @return Collection Collection of employee summary arrays.
     */
    public function search(int $companyId, array $filters = [], int $limit = 20): Collection
    {
        $query = Employee::where('company_id', $companyId)
            ->with(['designation', 'department', 'location']);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('employee_name', 'like', '%' . $term . '%')
                    ->orWhere('employee_code', 'like', '%' . $term . '%');
            });
        }

        if (!empty($filters['department'])) {
            $query->whereHas('department', fn ($q) => $q->where('department_name', 'like', '%' . $filters['department'] . '%'));
        }

        if (!empty($filters['designation'])) {
            $query->whereHas('designation', fn ($q) => $q->where('designation_name', 'like', '%' . $filters['designation'] . '%'));
        }

        if (!empty($filters['location'])) {
            $query->whereHas('location', fn ($q) => $q->where('location_name', 'like', '%' . $filters['location'] . '%'));
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNull('dol');
            } elseif ($filters['status'] === 'inactive') {
                $query->whereNotNull('dol');
            }
        }

        return $query->limit($limit)->get()->map(fn (Employee $e) => $this->toSummary($e));
    }

    /**
     * Find an employee by ID or employee code within a specific company.
     *
     * @param int $companyId The ID of the company.
     * @param int|null $employeeId The ID of the employee.
     * @param string|null $employeeCode The employee code.
     * @return Employee|null The employee model instance, or null if not found.
     */
    public function findForCompany(int $companyId, ?int $employeeId = null, ?string $employeeCode = null): ?Employee
    {
        $query = Employee::where('company_id', $companyId)
            ->with(['department', 'designation', 'location']);

        if ($employeeId) {
            return $query->find($employeeId);
        }

        if ($employeeCode) {
            return $query->where('employee_code', $employeeCode)->first();
        }

        return null;
    }

    /**
     * Resolve employee_id / employee_code from mixed tool or import input.
     * Non-numeric employee_id values are treated as employee_code (EMPNO) and kept as-is.
     *
     * @param array<string, mixed> $data
     * @return array{0: ?int, 1: ?string}
     */
    public function resolveEmployeeIdentifier(array $data): array
    {
        $employeeId = null;
        $employeeCode = null;

        if (isset($data['employee_code']) && trim((string) $data['employee_code']) !== '') {
            $employeeCode = trim((string) $data['employee_code']);
        }

        if (isset($data['employee_id']) && $data['employee_id'] !== '' && $data['employee_id'] !== null) {
            $raw = trim((string) $data['employee_id']);
            if ($raw !== '') {
                if (ctype_digit($raw)) {
                    $employeeId = (int) $raw;
                } else {
                    $employeeCode = $employeeCode ?? $raw;
                }
            }
        }

        return [$employeeId, $employeeCode];
    }

    /**
     * Create an employee from AI agent data.
     *
     * @param int $companyId The ID of the company.
     * @param array<string,mixed> $data Data provided by the AI agent.
     * @return array<string,mixed> Result of the creation.
     * @throws ValidationException If validation or creation fails.
     */
    public function createFromAgent(int $companyId, array $data): array
    {
        $normalized = $this->normalizeAgentRow($data);
        $result = $this->upsertFromImportRow($companyId, $normalized);

        if (!$result['success']) {
            throw ValidationException::withMessages(['employee' => $result['error'] ?? 'Failed to create employee.']);
        }

        $code = trim((string) ($normalized['employee_code'] ?? ''));
        $employee = Employee::where('company_id', $companyId)
            ->when($code !== '', fn ($q) => $q->where('employee_code', $code))
            ->latest('id')
            ->first();

        return ['action' => 'created', 'employee' => $this->toSummary($employee)];
    }

    /**
     * Update an employee from AI agent data.
     *
     * @param int $companyId The ID of the company.
     * @param array<string,mixed> $data Data provided by the AI agent.
     * @return array<string,mixed> Result of the update.
     * @throws ValidationException If employee is not found or update fails.
     */
    public function updateFromAgent(int $companyId, array $data): array
    {
        [$employeeId, $employeeCode] = $this->resolveEmployeeIdentifier($data);
        $employee = $this->findForCompany($companyId, $employeeId, $employeeCode);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => 'Employee not found in this company.']);
        }

        $normalized = $this->normalizeAgentRow($data, generateCodeIfMissing: false);
        $result = $this->upsertFromImportRow($companyId, $normalized, $employee);

        if (!$result['success']) {
            throw ValidationException::withMessages(['employee' => $result['error'] ?? 'Failed to update employee.']);
        }

        return ['action' => 'updated', 'employee' => $this->toSummary($employee->fresh(['department', 'designation', 'location']))];
    }

    /**
     * Detect the heading row in an Excel file.
     *
     * @param string $filePath Path to the Excel file.
     * @return int The detected heading row number.
     */
    public function detectHeadingRow(string $filePath): int
    {
        $maxScanRows = 40;

        $employeeCodeNeedles = [
            'employee code', 'emp code', 'empno', 'emp no', 'employee no', 'company code',
        ];
        $employeeNameNeedles = [
            'employee name', 'name', 'full name', 'first name', 'firstname',
        ];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = min($sheet->getHighestRow(), $maxScanRows);

            for ($row = 1; $row <= $highestRow; $row++) {
                $cells = [];
                foreach ($sheet->getRowIterator($row, $row) as $rowObj) {
                    foreach ($rowObj->getCellIterator() as $cell) {
                        $cells[] = $this->normalizeHeadingCell($cell->getValue());
                    }
                }

                $hasCode = false;
                $hasName = false;
                foreach ($cells as $cell) {
                    if ($cell === '') {
                        continue;
                    }
                    foreach ($employeeCodeNeedles as $needle) {
                        if (str_contains($cell, $needle)) {
                            $hasCode = true;
                            break;
                        }
                    }
                    foreach ($employeeNameNeedles as $needle) {
                        if (str_contains($cell, $needle)) {
                            $hasName = true;
                            break;
                        }
                    }
                }

                if ($hasCode && $hasName) {
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Could not detect heading row, defaulting to 1', ['error' => $e->getMessage()]);
        }

        return 1;
    }

    /**
     * Convert an employee model to a summary array.
     *
     * @param Employee|null $employee The employee model instance.
     * @return array<string,mixed> Summary array of employee details.
     */
    public function toSummary(?Employee $employee): array
    {
        if (!$employee) {
            return [];
        }

        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->employee_name,
            'father_name' => $employee->father_name,
            'gender' => $employee->gender,
            'dob' => $employee->dob?->format('Y-m-d'),
            'doj' => $employee->doj?->format('Y-m-d'),
            'dol' => $employee->dol?->format('Y-m-d'),
            'department' => $employee->department?->department_name,
            'designation' => $employee->designation?->designation_name,
            'location' => $employee->location?->location_name,
            'pf_no' => $employee->pf_no,
            'esi_no' => $employee->esi_no,
            'bank_name' => $employee->bank_name,
            'bank_account_no' => $employee->bank_account_no,
            'bank_ifsc_code' => $employee->bank_ifsc_code,
        ];
    }

    /**
     * Validate form data for employee creation/update.
     *
     * @param int $companyId The ID of the company.
     * @param array<string,mixed> $data The form data.
     * @param int|null $employeeId The ID of the employee (for unique validation).
     * @return array<string,mixed> Validated data.
     */
    private function validateFormData(int $companyId, array $data, ?int $employeeId): array
    {
        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'min:2'],
            'middle_name' => ['nullable', 'string'],
            'last_name' => ['required', 'string', 'min:2'],
            'father_name' => ['required', 'string', 'min:2'],
            'gender' => ['required', 'in:male,female,other,M,F,O'],
            'dob' => ['nullable', 'date'],
            'department' => ['required', 'string', 'max:200'],
            'designation' => ['required', 'string', 'max:200'],
            'location' => ['required', 'string', 'max:255'],
            'employee_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'employee_code')->ignore($employeeId),
            ],
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'esi_no' => ['nullable', 'string', 'max:20'],
            'pf_no' => ['nullable', 'string', 'max:20'],
            'account_no' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:200'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
        ]);

        $validator->validate();

        return $validator->validated();
    }

    /**
     * Ensure a department exists for a company.
     *
     * @param int $companyId The ID of the company.
     * @param string $name The name of the department.
     * @return Department The department model instance.
     */
    private function ensureDepartment(int $companyId, string $name): Department
    {
        return Department::firstOrCreate([
            'company_id' => $companyId,
            'department_name' => trim($name),
        ]);
    }

    /**
     * Ensure a designation exists for a company.
     *
     * @param int $companyId The ID of the company.
     * @param string $name The name of the designation.
     * @return Designation The designation model instance.
     */
    private function ensureDesignation(int $companyId, string $name): Designation
    {
        return Designation::firstOrCreate([
            'company_id' => $companyId,
            'designation_name' => trim($name),
        ]);
    }

    /**
     * Ensure a location exists for a company.
     *
     * @param int $companyId The ID of the company.
     * @param string $name The name of the location.
     * @return Location The location model instance.
     */
    private function ensureLocation(int $companyId, string $name): Location
    {
        $locationName = trim($name);
        $existing = Location::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(location_name) = ?', [Str::lower($locationName)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $base = preg_replace('/[^A-Z0-9]/', '', Str::upper($locationName));
        $prefix = Str::substr($base ?: 'LOC', 0, 6);
        $locationCode = $prefix . Str::upper(Str::random(4));

        return Location::create([
            'company_id' => $companyId,
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
    }

    /**
     * Ensure a department ID exists for a company, with caching.
     *
     * @param int $companyId The ID of the company.
     * @param string|null $name The name of the department.
     * @return int The department ID.
     */
    private function ensureDepartmentId(int $companyId, ?string $name): int
    {
        $departmentName = trim((string) $name);
        if ($departmentName === '') {
            $departmentName = 'General';
        }
        $key = Str::lower($departmentName);

        if (isset($this->departmentCache[$key])) {
            return $this->departmentCache[$key];
        }

        $department = Department::firstOrCreate([
            'company_id' => $companyId,
            'department_name' => $departmentName,
        ]);

        return $this->departmentCache[$key] = $department->id;
    }

    /**
     * Ensure a designation ID exists for a company, with caching.
     *
     * @param int $companyId The ID of the company.
     * @param string|null $name The name of the designation.
     * @return int The designation ID.
     */
    private function ensureDesignationId(int $companyId, ?string $name): int
    {
        $designationName = trim((string) $name);
        if ($designationName === '') {
            $designationName = 'Staff';
        }
        $key = Str::lower($designationName);

        if (isset($this->designationCache[$key])) {
            return $this->designationCache[$key];
        }

        $designation = Designation::firstOrCreate([
            'company_id' => $companyId,
            'designation_name' => $designationName,
        ]);

        return $this->designationCache[$key] = $designation->id;
    }

    /**
     * Ensure a location ID exists for a company, with caching.
     *
     * @param int $companyId The ID of the company.
     * @param string|null $name The name of the location.
     * @return int The location ID.
     */
    private function ensureLocationId(int $companyId, ?string $name): int
    {
        $locationName = trim((string) $name);
        if ($locationName === '') {
            $locationName = 'Default';
        }
        $key = Str::lower($locationName);

        if (isset($this->locationCache[$key])) {
            return $this->locationCache[$key];
        }

        $existing = Location::where('company_id', $companyId)
            ->whereRaw('LOWER(location_name) = ?', [$key])
            ->first();
        if ($existing) {
            return $this->locationCache[$key] = $existing->id;
        }

        $base = preg_replace('/[^A-Z0-9]/', '', Str::upper($locationName));
        $prefix = Str::substr($base ?: 'LOC', 0, 6);
        $locationCode = $prefix . Str::upper(Str::random(4));

        $location = Location::create([
            'company_id' => $companyId,
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

    /**
     * Generate a unique employee code.
     *
     * @return string The generated employee code.
     */
    public function generateEmployeeCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'EMP' . Str::upper(Str::random(10));
            if (!Employee::where('employee_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'EMP' . time();
    }

    /**
     * Check if a value is considered "filled" (not null and not empty string).
     *
     * @param mixed $value The value to check.
     * @return bool True if filled, false otherwise.
     */
    private function isFilled(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    /**
     * Normalize gender string to a single character (M, F, O).
     *
     * @param mixed $value The raw gender value.
     * @return string Normalized gender.
     */
    public function normalizeGender(mixed $value): string
    {
        $raw = Str::lower(trim((string) $value));
        if ($raw === '') {
            return 'O';
        }
        if (in_array($raw, ['m', 'male', 'पुरुष', 'purush'], true)) {
            return 'M';
        }
        if (in_array($raw, ['f', 'female', 'महिला', 'mahila'], true)) {
            return 'F';
        }
        if (in_array($raw, ['o', 'other', 'अन्य'], true)) {
            return 'O';
        }

        return 'O';
    }

    /**
     * Parse a date value from various formats.
     *
     * @param mixed $value The date value to parse.
     * @return string|null Parsed date in Y-m-d format, or null on failure.
     */
    private function parseDate(mixed $value): ?string
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

    /**
     * Normalize a row of data from an AI agent.
     *
     * @param array<string,mixed> $row The raw row data.
     * @return array<string,mixed> Normalized row data.
     */
    private function normalizeAgentRow(array $row, bool $generateCodeIfMissing = true): array
    {
        $normalized = $row;

        if (!empty($row['first_name']) || !empty($row['last_name'])) {
            $normalized['employee_name'] = trim(implode(' ', array_filter([
                $row['first_name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
            ])));
        }

        if (!empty($row['gender'])) {
            $normalized['gender'] = $this->normalizeGender($row['gender']);
        }

        if (empty($normalized['employee_code']) && !empty($row['employee_company_code'])) {
            $normalized['employee_code'] = trim((string) $row['employee_company_code']);
        }

        if (!empty($normalized['employee_code'])) {
            $normalized['employee_code'] = trim((string) $normalized['employee_code']);
        }

        foreach (['dob', 'doj', 'dol', 'joining_date', 'leaving_date'] as $dateField) {
            if (!empty($row[$dateField])) {
                $target = in_array($dateField, ['joining_date'], true) ? 'doj' : (in_array($dateField, ['leaving_date'], true) ? 'dol' : $dateField);
                $normalized[$target] = $this->parseDate($row[$dateField]);
            }
        }

        if ($generateCodeIfMissing && empty($normalized['employee_code'])) {
            $normalized['employee_code'] = $this->generateEmployeeCode();
        }

        return $normalized;
    }

    /**
     * Normalize a heading cell for comparison.
     *
     * @param mixed $value The heading cell value.
     * @return string Normalized heading text.
     */
    private function normalizeHeadingCell(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        $text = Str::lower($text);
        $text = str_replace(['_', '-', '/', '\\', '.', ':', '(', ')', '[', ']', '{', '}', "\n", "\r", "\t"], ' ', $text);
        $text = preg_replace('/\\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}

