<?php

namespace App\Services\Ai\Tools;

use App\Imports\EmployeeImport;
use App\Services\Ai\Contracts\AiTool;
use App\Services\Employee\EmployeeService;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeToolProvider
{
    /**
     * @return array<int, AiTool>
     */
    public static function tools(): array
    {
        return [
            new SearchEmployeesTool(),
            new GetEmployeeTool(),
            new CreateEmployeeTool(),
            new UpdateEmployeeTool(),
            new BulkUpsertEmployeesTool(),
            new ImportEmployeesExcelTool(),
        ];
    }
}

class SearchEmployeesTool implements AiTool
{
    public function name(): string
    {
        return 'search_employees';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Search and filter employees in the current company by name, code, department, designation, location, or active/inactive status.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Search term for employee name or code'],
                    'department' => ['type' => 'string'],
                    'designation' => ['type' => 'string'],
                    'location' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                    'limit' => ['type' => 'integer', 'description' => 'Max results (default 20)'],
                ],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        $service = app(EmployeeService::class);
        $limit = min((int) ($args['limit'] ?? 20), 50);
        $employees = $service->search($companyId, $args, $limit);

        return [
            'success' => true,
            'count' => $employees->count(),
            'employees' => $employees->values()->all(),
        ];
    }

    public function isMutating(): bool
    {
        return false;
    }
}

class GetEmployeeTool implements AiTool
{
    public function name(): string
    {
        return 'get_employee';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Get full details of one employee by employee_id or employee_code.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'employee_code' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        $service = app(EmployeeService::class);
        $employee = $service->findForCompany(
            $companyId,
            isset($args['employee_id']) ? (int) $args['employee_id'] : null,
            $args['employee_code'] ?? null
        );

        if (!$employee) {
            return ['success' => false, 'error' => 'Employee not found.'];
        }

        return ['success' => true, 'employee' => $service->toSummary($employee)];
    }

    public function isMutating(): bool
    {
        return false;
    }
}

class CreateEmployeeTool implements AiTool
{
    public function name(): string
    {
        return 'create_employee';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Create a single new employee. Provide employee_name or first_name+last_name, department, designation, location.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employee_code' => ['type' => 'string'],
                    'employee_name' => ['type' => 'string'],
                    'first_name' => ['type' => 'string'],
                    'middle_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'father_name' => ['type' => 'string'],
                    'gender' => ['type' => 'string', 'description' => 'male/female/other or M/F/O or Hindi'],
                    'dob' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'doj' => ['type' => 'string', 'description' => 'Joining date YYYY-MM-DD'],
                    'dol' => ['type' => 'string', 'description' => 'Leaving date YYYY-MM-DD'],
                    'department' => ['type' => 'string'],
                    'designation' => ['type' => 'string'],
                    'location' => ['type' => 'string'],
                    'pf_no' => ['type' => 'string'],
                    'esi_no' => ['type' => 'string'],
                    'bank_name' => ['type' => 'string'],
                    'bank_account_no' => ['type' => 'string'],
                    'bank_ifsc_code' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        try {
            $result = app(EmployeeService::class)->createFromAgent($companyId, $args);

            return ['success' => true, ...$result];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => collect($e->errors())->flatten()->first()];
        }
    }

    public function isMutating(): bool
    {
        return true;
    }
}

class UpdateEmployeeTool implements AiTool
{
    public function name(): string
    {
        return 'update_employee';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Update an existing employee by employee_id or employee_code. Only provided fields are updated.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'employee_code' => ['type' => 'string'],
                    'employee_name' => ['type' => 'string'],
                    'first_name' => ['type' => 'string'],
                    'middle_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'father_name' => ['type' => 'string'],
                    'gender' => ['type' => 'string'],
                    'dob' => ['type' => 'string'],
                    'doj' => ['type' => 'string'],
                    'dol' => ['type' => 'string'],
                    'department' => ['type' => 'string'],
                    'designation' => ['type' => 'string'],
                    'location' => ['type' => 'string'],
                    'pf_no' => ['type' => 'string'],
                    'esi_no' => ['type' => 'string'],
                    'bank_name' => ['type' => 'string'],
                    'bank_account_no' => ['type' => 'string'],
                    'bank_ifsc_code' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if (empty($args['employee_id']) && empty($args['employee_code'])) {
            return ['success' => false, 'error' => 'employee_id or employee_code is required.'];
        }

        try {
            $result = app(EmployeeService::class)->updateFromAgent($companyId, $args);

            return ['success' => true, ...$result];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => collect($e->errors())->flatten()->first()];
        }
    }

    public function isMutating(): bool
    {
        return true;
    }
}

class BulkUpsertEmployeesTool implements AiTool
{
    public function name(): string
    {
        return 'bulk_upsert_employees';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Create or update multiple employees at once. Upserts by employee_code.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employees' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'employee_code' => ['type' => 'string'],
                                'employee_name' => ['type' => 'string'],
                                'first_name' => ['type' => 'string'],
                                'last_name' => ['type' => 'string'],
                                'father_name' => ['type' => 'string'],
                                'gender' => ['type' => 'string'],
                                'department' => ['type' => 'string'],
                                'designation' => ['type' => 'string'],
                                'location' => ['type' => 'string'],
                                'doj' => ['type' => 'string'],
                                'pf_no' => ['type' => 'string'],
                                'esi_no' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'required' => ['employees'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        $employees = $args['employees'] ?? [];
        if (!is_array($employees) || empty($employees)) {
            return ['success' => false, 'error' => 'No employees provided.'];
        }

        $result = app(EmployeeService::class)->upsertMany($companyId, $employees);

        return [
            'success' => $result['failed'] === 0,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'failed' => $result['failed'],
            'errors' => $result['errors'],
        ];
    }

    public function isMutating(): bool
    {
        return true;
    }
}

class ImportEmployeesExcelTool implements AiTool
{
    public function name(): string
    {
        return 'import_employees_excel';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Import employees from an uploaded Excel or CSV file. Requires file_path from system context.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'file_path' => ['type' => 'string', 'description' => 'Absolute path to the uploaded Excel/CSV file'],
                ],
                'required' => ['file_path'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        $filePath = $args['file_path'] ?? '';
        if ($filePath === '' || !file_exists($filePath)) {
            return ['success' => false, 'error' => 'Excel file not found. Please upload a file first.'];
        }

        $service = app(EmployeeService::class);
        $headingRow = $service->detectHeadingRow($filePath);
        $import = new EmployeeImport($companyId, $headingRow);

        Excel::import($import, $filePath);

        $stats = $import->getImportStats();
        $errors = $import->getErrors();

        return [
            'success' => $stats['failed'] === 0,
            'imported' => $stats['imported'],
            'failed' => $stats['failed'],
            'heading_row' => $headingRow,
            'errors' => array_slice($errors, 0, 20),
        ];
    }

    public function isMutating(): bool
    {
        return true;
    }
}
