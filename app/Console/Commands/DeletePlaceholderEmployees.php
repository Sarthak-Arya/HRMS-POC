<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class DeletePlaceholderEmployees extends Command
{
    protected $signature = 'employees:delete-placeholder
        {--company_id= : Only delete employees from this company_id}
        {--prefix=Employee  : Name prefix to match (default: "Employee ")}
        {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete placeholder employees whose name starts with a given prefix (e.g., "Employee EMP...").';

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $prefix = (string) $this->option('prefix');
        $dryRun = (bool) $this->option('dry-run');

        $prefix = $prefix === '' ? 'Employee ' : $prefix;

        $query = Employee::query()
            ->where('employee_name', 'like', $prefix . '%');

        if ($companyId !== null && $companyId !== '') {
            if (!ctype_digit((string) $companyId)) {
                $this->error('Invalid --company_id (must be an integer).');
                return self::FAILURE;
            }
            $query->where('company_id', (int) $companyId);
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->info('No placeholder employees found.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} employees with name starting with: \"{$prefix}\"");

        $preview = (clone $query)
            ->orderBy('id')
            ->limit(20)
            ->get(['id', 'company_id', 'employee_code', 'employee_name']);

        $this->table(
            ['id', 'company_id', 'employee_code', 'employee_name'],
            $preview->map(fn ($e) => [$e->id, $e->company_id, $e->employee_code, $e->employee_name])->all()
        );

        if ($dryRun) {
            $this->comment('Dry run: nothing deleted.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Delete these employees (and any others matching this filter)?', false)) {
            $this->comment('Aborted.');
            return self::SUCCESS;
        }

        $deleted = 0;

        try {
            (clone $query)
                ->orderBy('id')
                ->chunkById(500, function ($employees) use (&$deleted) {
                    $ids = $employees->pluck('id')->all();
                    if (empty($ids)) {
                        return;
                    }
                    $deleted += Employee::whereIn('id', $ids)->delete();
                });
        } catch (QueryException $e) {
            $this->error('Database error while deleting employees: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Deleted {$deleted} employees.");
        return self::SUCCESS;
    }
}

