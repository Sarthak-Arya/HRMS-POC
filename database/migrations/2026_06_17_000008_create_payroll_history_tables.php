<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable();
            $table->json('snapshot_json');
            $table->timestamp('created_at');

            $table->unique(['payroll_run_id', 'version_no'], 'prh_run_version_unique');
            $table->index(['payroll_run_id', 'created_at'], 'prh_run_created_idx');
        });

        Schema::create('employee_payroll_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_payroll_id')->constrained('employee_payrolls')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable();
            $table->json('snapshot_json');
            $table->timestamp('created_at');

            $table->unique(['employee_payroll_id', 'version_no'], 'eph_payroll_version_unique');
            $table->index(['employee_payroll_id', 'created_at'], 'eph_payroll_created_idx');
        });

        Schema::create('employee_payroll_line_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_payroll_line_id')->constrained('employee_payroll_lines')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable();
            $table->json('snapshot_json');
            $table->timestamp('created_at');

            $table->unique(['employee_payroll_line_id', 'version_no'], 'eplh_line_version_unique');
            $table->index(['employee_payroll_line_id', 'created_at'], 'eplh_line_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_line_history');
        Schema::dropIfExists('employee_payroll_history');
        Schema::dropIfExists('payroll_run_history');
    }
};
