<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_leave_exception_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('attendance_policies')->nullOnDelete();
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            $table->decimal('max_days_per_month', 5, 2)->nullable();
            $table->decimal('max_days_per_year', 5, 2)->nullable();
            $table->string('on_exceed', 30)->default('block');
            $table->string('payroll_action', 30)->default('none');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'policy_id', 'leave_type_id'], 'att_leave_exc_rules_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_leave_exception_rules');
    }
};
