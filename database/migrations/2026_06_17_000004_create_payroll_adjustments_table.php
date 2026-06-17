<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('compensation_components')->nullOnDelete();
            $table->string('adjustment_type', 20);
            $table->decimal('amount', 14, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['payroll_run_id', 'employee_id']);
            $table->index(['employee_id', 'created_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `chk_payroll_adjustments_type` CHECK (`adjustment_type` IN ('ADDITION', 'DEDUCTION'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
