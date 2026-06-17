<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permission_role')) {
            Schema::drop('permission_role');
        }

        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'slug')) {
            Schema::drop('permissions');
        }

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'slug')) {
            Schema::drop('roles');
        }
    }

    public function down(): void
    {
        //
    }
};
