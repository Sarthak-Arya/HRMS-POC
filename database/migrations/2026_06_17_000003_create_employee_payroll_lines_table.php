<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_payroll_id')->constrained('employee_payrolls')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('compensation_components')->nullOnDelete();
            $table->string('component_name');
            $table->string('component_type', 30);
            $table->decimal('calculated_amount', 14, 2)->default(0);
            $table->json('calculation_basis')->nullable();
            $table->timestamps();

            $table->index('employee_payroll_id');
            $table->index('component_type');
            $table->index('component_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `employee_payroll_lines` ADD CONSTRAINT `chk_employee_payroll_lines_component_type` CHECK (`component_type` IN ('EARNING', 'DEDUCTION', 'EMPLOYER_CONTRIBUTION'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_lines');
    }
};
