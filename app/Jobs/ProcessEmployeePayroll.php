<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\Observability\DomainTelemetry;
use App\Services\Payroll\PayrollGenerationService;
use App\Support\Observability\TelemetryContext;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmployeePayroll implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $payrollRunId,
        public int $employeeId,
        public ?string $requestId = null,
        public ?string $traceId = null,
    ) {}

    public function handle(PayrollGenerationService $generationService, DomainTelemetry $telemetry): void
    {
        if ($this->requestId) {
            TelemetryContext::forJob($this->requestId, $this->traceId);
        }

        if ($this->batch()?->cancelled()) {
            return;
        }

        $run = PayrollRun::query()->findOrFail($this->payrollRunId);
        $employee = Employee::query()->findOrFail($this->employeeId);

        try {
            $generationService->processEmployee($run, $employee);
        } catch (\Throwable $e) {
            $telemetry->emit('payroll.job.failed', 'business', 'failure', [
                'company.id' => $run->company_id,
                'payroll.run_id' => $this->payrollRunId,
                'job.attempts' => $this->attempts(),
                'error.type' => $e::class,
            ], 'error');
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function middleware(): array
    {
        return [];
    }
}
