<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('manager_id')
                ->nullable()
                ->after('user_id')
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('work_email', 191)->nullable()->after('employee_name');
            $table->string('phone', 30)->nullable()->after('work_email');
            $table->string('emergency_contact_name', 200)->nullable()->after('permanent_country');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');

            $table->unique('user_id');
            $table->index(['company_id', 'manager_id']);
            $table->index(['company_id', 'work_email']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropIndex(['company_id', 'manager_id']);
            $table->dropIndex(['company_id', 'work_email']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn([
                'work_email',
                'phone',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
