<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('loan_name');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('emi_amount', 14, 2);
            $table->unsignedTinyInteger('start_month');
            $table->unsignedSmallInteger('start_year');
            $table->decimal('remaining_amount', 14, 2);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `employee_loans` ADD CONSTRAINT `chk_employee_loans_status` CHECK (`status` IN ('ACTIVE', 'CLOSED', 'HOLD'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
