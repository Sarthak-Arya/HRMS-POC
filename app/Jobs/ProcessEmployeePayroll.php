<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollGenerationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmployeePayroll implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $payrollRunId,
        public int $employeeId,
    ) {}

    public function handle(PayrollGenerationService $generationService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $run = PayrollRun::query()->findOrFail($this->payrollRunId);
        $employee = Employee::query()->findOrFail($this->employeeId);

        try {
            $generationService->processEmployee($run, $employee);
        } catch (\Throwable $e) {
            Log::error("Payroll processing failed for employee {$this->employeeId} on run {$this->payrollRunId}: {$e->getMessage()}");
            throw $e;
        }
    }
}
