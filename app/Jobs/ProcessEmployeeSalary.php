<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\CompensationStructure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeSalary implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Employee $employee,
        protected string $fromDate,
        protected string $toDate
    ) {}

    public function handle()
    {
        try {
            // Check if salary already exists
            if (Salary::where('employee_id', $this->employee->id)
                ->where('from_date', $this->fromDate)
                ->where('to_date', $this->toDate)
                ->exists()) {
                return;
            }

            // Get compensation structure
            $compensation = CompensationStructure::find($this->employee->compensation_id);
            if (!$compensation) {
                throw new \Exception("No compensation structure found for employee {$this->employee->id}");
            }

            // Calculate salary components
            $basic = $compensation->basic;
            $components = $compensation->components;
            $salaryBreakdown = ['basic' => $basic];

            foreach ($components as $component => $percentage) {
                $salaryBreakdown[$component] = round($basic * ($percentage / 100), 2);
            }

            // Calculate total
            $salaryBreakdown['total'] = array_sum($salaryBreakdown);

            // Create salary record
            Salary::create([
                'employee_id' => $this->employee->id,
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
                'salary_json' => $salaryBreakdown
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process salary for employee {$this->employee->id}: " . $e->getMessage());
            throw $e;
        }
    }
} 