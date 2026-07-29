<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->json('alternate_saturday_weeks')->nullable()->after('custom_weekly_off_days');
            $table->boolean('paid_holidays')->default(true)->after('allow_negative_leave_balance');
            $table->boolean('paid_weekly_offs')->default(true)->after('paid_holidays');
            $table->boolean('require_attendance_before_holiday')->default(false)->after('paid_weekly_offs');
            $table->boolean('require_attendance_after_holiday')->default(false)->after('require_attendance_before_holiday');
            $table->boolean('auto_adjust_approved_leave')->default(false)->after('require_attendance_after_holiday');
            $table->boolean('sandwich_leave_enabled')->default(false)->after('auto_adjust_approved_leave');
            $table->boolean('comp_off_enabled')->default(false)->after('sandwich_leave_enabled');
            $table->string('leave_balance_priority_mode', 30)->default('policy_order')->after('comp_off_enabled');
            $table->boolean('auto_apply_week_offs')->default(true)->after('leave_balance_priority_mode');
            $table->boolean('auto_apply_holidays')->default(true)->after('auto_apply_week_offs');
            $table->boolean('include_public_holidays')->default(true)->after('auto_apply_holidays');
            $table->boolean('include_regional_holidays')->default(true)->after('include_public_holidays');
            $table->boolean('include_emergency_holidays')->default(true)->after('include_regional_holidays');
        });

        Schema::table('attendance_policy_leave_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('deduction_priority')->default(100)->after('encashable');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_policy_leave_types', function (Blueprint $table) {
            $table->dropColumn('deduction_priority');
        });

        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'alternate_saturday_weeks',
                'paid_holidays',
                'paid_weekly_offs',
                'require_attendance_before_holiday',
                'require_attendance_after_holiday',
                'auto_adjust_approved_leave',
                'sandwich_leave_enabled',
                'comp_off_enabled',
                'leave_balance_priority_mode',
                'auto_apply_week_offs',
                'auto_apply_holidays',
                'include_public_holidays',
                'include_regional_holidays',
                'include_emergency_holidays',
            ]);
        });
    }
};
