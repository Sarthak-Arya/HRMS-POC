<?php

namespace App\Services\Ai\Tools;

use App\Enums\Permission;
use App\Models\User;
use App\Services\Ai\Contracts\AiTool;
use App\Services\Attendance\AttendanceService;
use Illuminate\Validation\ValidationException;

class AttendanceToolProvider
{
    /**
     * @return array<int, AiTool>
     */
    public static function tools(): array
    {
        return [
            new SearchAttendanceTool(),
            new GetAttendanceTool(),
            new UpsertAttendanceTool(),
            new BulkUpsertAttendanceTool(),
            new ImportAttendanceExcelTool(),
        ];
    }
}

trait ChecksAttendancePermission
{
    private function assertCanViewAttendance(int $userId): ?array
    {
        $user = User::find($userId);
        if (!$user || !$user->hasAnyPermission(Permission::AttendanceView, Permission::AttendanceManage)) {
            return ['success' => false, 'error' => 'You do not have permission to view attendance.'];
        }

        return null;
    }

    private function assertCanManageAttendance(int $userId): ?array
    {
        $user = User::find($userId);
        if (!$user || !$user->hasPermission(Permission::AttendanceManage)) {
            return ['success' => false, 'error' => 'You do not have permission to manage attendance.'];
        }

        return null;
    }
}

class SearchAttendanceTool implements AiTool
{
    use ChecksAttendancePermission;

    public function name(): string
    {
        return 'search_attendance';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Search monthly attendance records for a given month and year. Filter by employee name/code or department.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'month' => ['type' => 'integer', 'description' => 'Month 1-12'],
                    'year' => ['type' => 'integer', 'description' => 'Year e.g. 2026'],
                    'search' => ['type' => 'string', 'description' => 'Employee name or code'],
                    'department' => ['type' => 'string'],
                    'limit' => ['type' => 'integer', 'description' => 'Max results (default 20)'],
                ],
                'required' => ['month', 'year'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if ($denied = $this->assertCanViewAttendance($userId)) {
            return $denied;
        }

        $month = (int) ($args['month'] ?? 0);
        $year = (int) ($args['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return ['success' => false, 'error' => 'Valid month (1-12) and year are required.'];
        }

        $service = app(AttendanceService::class);
        $limit = min((int) ($args['limit'] ?? 20), 50);
        $records = $service->search($companyId, $month, $year, $args, $limit);

        return [
            'success' => true,
            'count' => $records->count(),
            'attendance' => $records->values()->all(),
        ];
    }

    public function isMutating(): bool
    {
        return false;
    }
}

class GetAttendanceTool implements AiTool
{
    use ChecksAttendancePermission;

    public function name(): string
    {
        return 'get_attendance';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Get monthly attendance for one employee by employee_id or employee_code for a specific month and year.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'employee_code' => ['type' => 'string'],
                    'month' => ['type' => 'integer', 'description' => 'Month 1-12'],
                    'year' => ['type' => 'integer', 'description' => 'Year e.g. 2026'],
                ],
                'required' => ['month', 'year'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if ($denied = $this->assertCanViewAttendance($userId)) {
            return $denied;
        }

        [$employeeId, $employeeCode] = app(\App\Services\Employee\EmployeeService::class)->resolveEmployeeIdentifier($args);
        if ($employeeId === null && $employeeCode === null) {
            return ['success' => false, 'error' => 'employee_id or employee_code is required.'];
        }

        $month = (int) ($args['month'] ?? 0);
        $year = (int) ($args['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return ['success' => false, 'error' => 'Valid month (1-12) and year are required.'];
        }

        $service = app(AttendanceService::class);
        $record = $service->findForEmployee(
            $companyId,
            $month,
            $year,
            $employeeId,
            $employeeCode,
        );

        if (!$record) {
            return ['success' => false, 'error' => 'Attendance record not found for this employee and period.'];
        }

        return ['success' => true, 'attendance' => $service->toSummary($record)];
    }

    public function isMutating(): bool
    {
        return false;
    }
}

class UpsertAttendanceTool implements AiTool
{
    use ChecksAttendancePermission;

    public function name(): string
    {
        return 'upsert_attendance';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Add or update monthly attendance for one employee. Creates a new record or updates an existing one for the given month/year. worked_days is auto-calculated from total_days minus leaves.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'employee_code' => ['type' => 'string'],
                    'month' => ['type' => 'integer', 'description' => 'Month 1-12'],
                    'year' => ['type' => 'integer', 'description' => 'Year e.g. 2026'],
                    'cl' => ['type' => 'number', 'description' => 'Casual leave days'],
                    'el' => ['type' => 'number', 'description' => 'Earned leave days'],
                    'sl' => ['type' => 'number', 'description' => 'Sick leave days'],
                    'esi_leave' => ['type' => 'number', 'description' => 'ESI leave days'],
                    'holiday' => ['type' => 'number', 'description' => 'Holiday days'],
                    'tot_dys' => ['type' => 'number', 'description' => 'Total days in month'],
                    'overtime_days' => ['type' => 'number'],
                    'overtime_hours' => ['type' => 'number'],
                    'deductions' => [
                        'type' => 'array',
                        'items' => ['type' => 'number'],
                        'description' => 'Attendance deduction amounts',
                    ],
                ],
                'required' => ['month', 'year'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if ($denied = $this->assertCanManageAttendance($userId)) {
            return $denied;
        }

        [$employeeId, $employeeCode] = app(\App\Services\Employee\EmployeeService::class)->resolveEmployeeIdentifier($args);
        if ($employeeId === null && $employeeCode === null) {
            return ['success' => false, 'error' => 'employee_id or employee_code is required.'];
        }

        try {
            $result = app(AttendanceService::class)->upsertFromAgent($companyId, $args);

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

class BulkUpsertAttendanceTool implements AiTool
{
    use ChecksAttendancePermission;

    public function name(): string
    {
        return 'bulk_upsert_attendance';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Add or update monthly attendance for multiple employees at once.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'records' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'employee_id' => ['type' => 'integer'],
                                'employee_code' => ['type' => 'string'],
                                'month' => ['type' => 'integer'],
                                'year' => ['type' => 'integer'],
                                'cl' => ['type' => 'number'],
                                'el' => ['type' => 'number'],
                                'sl' => ['type' => 'number'],
                                'esi_leave' => ['type' => 'number'],
                                'holiday' => ['type' => 'number'],
                                'tot_dys' => ['type' => 'number'],
                                'deductions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'number'],
                                ],
                            ],
                        ],
                    ],
                ],
                'required' => ['records'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if ($denied = $this->assertCanManageAttendance($userId)) {
            return $denied;
        }

        $records = $args['records'] ?? [];
        if (!is_array($records) || empty($records)) {
            return ['success' => false, 'error' => 'No attendance records provided.'];
        }

        $result = app(AttendanceService::class)->bulkUpsertFromAgent($companyId, $records);

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

class ImportAttendanceExcelTool implements AiTool
{
    use ChecksAttendancePermission;

    public function name(): string
    {
        return 'import_attendance_excel';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Import monthly attendance from an uploaded Excel file. Pass file_path only — employee codes/IDs in the file are read as-is; do not rewrite rows with bulk_upsert_attendance.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'file_path' => ['type' => 'string', 'description' => 'Absolute path to the uploaded Excel file'],
                    'month' => ['type' => 'integer', 'description' => 'Default month (1-12) when rows omit month'],
                    'year' => ['type' => 'integer', 'description' => 'Default year when rows omit year'],
                ],
                'required' => ['file_path'],
            ],
        ];
    }

    public function handle(array $args, int $companyId, int $userId): array
    {
        if ($denied = $this->assertCanManageAttendance($userId)) {
            return $denied;
        }

        $filePath = $args['file_path'] ?? '';
        if ($filePath === '' || !file_exists($filePath)) {
            return ['success' => false, 'error' => 'Excel file not found. Please upload a file first.'];
        }

        $result = app(AttendanceService::class)->importFromExcel(
            $companyId,
            $filePath,
            isset($args['month']) ? (int) $args['month'] : null,
            isset($args['year']) ? (int) $args['year'] : null,
        );

        return [
            'success' => $result['failed'] === 0,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
            'errors' => array_slice($result['errors'], 0, 20, true),
        ];
    }

    public function isMutating(): bool
    {
        return true;
    }
}
