<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('attendance_summary_id')->constrained('attendance')->restrictOnDelete();
            $table->foreignId('employee_compensation_id')->constrained('employee_compensation_history')->restrictOnDelete();
            $table->decimal('gross_earnings', 14, 2)->default(0);
            $table->decimal('gross_deductions', 14, 2)->default(0);
            $table->decimal('employer_contributions', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['payroll_run_id', 'status']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `employee_payrolls` ADD CONSTRAINT `chk_employee_payrolls_status` CHECK (`status` IN ('DRAFT', 'APPROVED', 'PAID'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
