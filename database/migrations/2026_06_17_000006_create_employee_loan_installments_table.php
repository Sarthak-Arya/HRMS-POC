<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->restrictOnDelete();
            $table->unsignedInteger('installment_no');
            $table->decimal('amount', 14, 2);
            $table->date('deducted_on');
            $table->timestamps();

            $table->unique(['loan_id', 'payroll_run_id']);
            $table->index(['loan_id', 'installment_no']);
            $table->index('payroll_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_installments');
    }
};
