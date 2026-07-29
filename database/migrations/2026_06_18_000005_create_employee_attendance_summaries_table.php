<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignId('policy_id')->nullable()->constrained('attendance_policies')->nullOnDelete();
            $table->string('entry_source', 20)->default('manual');
            $table->decimal('calendar_days', 5, 2)->default(0);
            $table->decimal('working_days', 5, 2)->default(0);
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('half_days', 5, 2)->default(0);
            $table->decimal('paid_leave_days', 5, 2)->default(0);
            $table->decimal('lop_days', 5, 2)->default(0);
            $table->decimal('weekly_off_days', 5, 2)->default(0);
            $table->decimal('holiday_days', 5, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('worked_days', 5, 2)->default(0);
            $table->decimal('overtime_days', 5, 2)->default(0);
            $table->decimal('total_days', 5, 2)->default(0);
            $table->decimal('prev_leave_days', 5, 2)->default(0);
            $table->decimal('prev_leave_amount', 12, 2)->default(0);
            $table->decimal('esi_la', 12, 2)->default(0);
            $table->string('shift_code', 20)->nullable();
            $table->decimal('ded_1', 12, 2)->default(0);
            $table->decimal('ded_2', 12, 2)->default(0);
            $table->decimal('ded_3', 12, 2)->default(0);
            $table->json('deductions')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['company_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_summaries');
    }
};
