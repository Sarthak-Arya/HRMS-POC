<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_comp_off_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->date('reference_date');
            $table->string('entry_type', 20);
            $table->decimal('days', 5, 2);
            $table->string('reason', 255)->nullable();
            $table->foreignId('attendance_id')->nullable()->constrained('employee_attendance')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_comp_off_ledger');
    }
};
