<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_payrolls') && Schema::hasColumn('employee_payrolls', 'attendance_summary_id')) {
            Schema::table('employee_payrolls', function (Blueprint $table) {
                $table->dropForeign(['attendance_summary_id']);
            });

            Schema::table('employee_payrolls', function (Blueprint $table) {
                $table->foreign('attendance_summary_id')
                    ->references('id')
                    ->on('employee_attendance_summaries')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('payroll_header') && Schema::hasColumn('payroll_header', 'attendance_id')) {
            Schema::table('payroll_header', function (Blueprint $table) {
                $table->dropForeign(['attendance_id']);
            });

            Schema::table('payroll_header', function (Blueprint $table) {
                $table->foreign('attendance_id')
                    ->references('id')
                    ->on('employee_attendance_summaries')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance')) {
            return;
        }

        if (Schema::hasTable('employee_payrolls') && Schema::hasColumn('employee_payrolls', 'attendance_summary_id')) {
            Schema::table('employee_payrolls', function (Blueprint $table) {
                $table->dropForeign(['attendance_summary_id']);
            });

            Schema::table('employee_payrolls', function (Blueprint $table) {
                $table->foreign('attendance_summary_id')
                    ->references('id')
                    ->on('attendance')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('payroll_header') && Schema::hasColumn('payroll_header', 'attendance_id')) {
            Schema::table('payroll_header', function (Blueprint $table) {
                $table->dropForeign(['attendance_id']);
            });

            Schema::table('payroll_header', function (Blueprint $table) {
                $table->foreign('attendance_id')
                    ->references('id')
                    ->on('attendance')
                    ->restrictOnDelete();
            });
        }
    }
};
