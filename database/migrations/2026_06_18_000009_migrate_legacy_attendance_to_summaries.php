<?php

use App\Services\Attendance\AttendanceSetupService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company')) {
            return;
        }

        $setup = app(AttendanceSetupService::class);

        foreach (DB::table('company')->pluck('id') as $companyId) {
            $setup->seedCompanyDefaults((int) $companyId);
        }

        if (! Schema::hasTable('attendance')) {
            return;
        }

        $setup->migrateLegacyAttendanceRows();

        $maxId = DB::table('employee_attendance_summaries')->max('id');
        if ($maxId !== null && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employee_attendance_summaries AUTO_INCREMENT = '.((int) $maxId + 1));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_attendance_summary_leaves')) {
            DB::table('employee_attendance_summary_leaves')->truncate();
        }

        if (Schema::hasTable('employee_attendance_summaries')) {
            DB::table('employee_attendance_summaries')->truncate();
        }

        if (Schema::hasTable('attendance_policy_assignments')) {
            DB::table('attendance_policy_assignments')->truncate();
        }

        if (Schema::hasTable('attendance_policy_leave_types')) {
            DB::table('attendance_policy_leave_types')->truncate();
        }

        if (Schema::hasTable('attendance_policies')) {
            DB::table('attendance_policies')->truncate();
        }

        if (Schema::hasTable('leave_types')) {
            DB::table('leave_types')->truncate();
        }
    }
};
