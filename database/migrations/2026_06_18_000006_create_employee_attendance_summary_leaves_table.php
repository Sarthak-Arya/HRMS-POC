<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_summary_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('summary_id')->constrained('employee_attendance_summaries')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->decimal('days', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['summary_id', 'leave_type_id'], 'emp_att_summary_leaves_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_summary_leaves');
    }
};
