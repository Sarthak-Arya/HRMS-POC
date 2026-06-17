<?php

namespace App\Services\Payroll;

use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollHistory;
use App\Models\EmployeePayrollLine;
use App\Models\EmployeePayrollLineHistory;
use App\Models\PayrollRun;
use App\Models\PayrollRunHistory;
use Illuminate\Database\Eloquent\Model;

class PayrollHistoryRecorder
{
    public function snapshotPayrollRun(PayrollRun $run, ?int $changedBy = null, ?string $reason = null): PayrollRunHistory
    {
        return $this->record($run, PayrollRunHistory::class, 'payroll_run_id', $changedBy, $reason);
    }

    public function snapshotEmployeePayroll(EmployeePayroll $payroll, ?int $changedBy = null, ?string $reason = null): EmployeePayrollHistory
    {
        return $this->record($payroll, EmployeePayrollHistory::class, 'employee_payroll_id', $changedBy, $reason);
    }

    public function snapshotEmployeePayrollLine(EmployeePayrollLine $line, ?int $changedBy = null, ?string $reason = null): EmployeePayrollLineHistory
    {
        return $this->record($line, EmployeePayrollLineHistory::class, 'employee_payroll_line_id', $changedBy, $reason);
    }

    /**
     * @template T of Model
     * @param class-string<T> $historyModel
     */
    private function record(
        Model $source,
        string $historyModel,
        string $foreignKey,
        ?int $changedBy,
        ?string $reason,
    ): Model {
        $latestVersion = $historyModel::query()
            ->where($foreignKey, $source->getKey())
            ->max('version_no');

        return $historyModel::create([
            $foreignKey => $source->getKey(),
            'version_no' => ($latestVersion ?? 0) + 1,
            'changed_by' => $changedBy,
            'change_reason' => $reason,
            'snapshot_json' => $source->toArray(),
            'created_at' => now(),
        ]);
    }
}
