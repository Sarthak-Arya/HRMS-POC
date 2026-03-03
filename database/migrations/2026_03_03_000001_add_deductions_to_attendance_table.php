<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'ded_1')) {
                $table->decimal('ded_1', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('attendance', 'ded_2')) {
                $table->decimal('ded_2', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('attendance', 'ded_3')) {
                $table->decimal('ded_3', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'ded_1')) {
                $table->dropColumn('ded_1');
            }
            if (Schema::hasColumn('attendance', 'ded_2')) {
                $table->dropColumn('ded_2');
            }
            if (Schema::hasColumn('attendance', 'ded_3')) {
                $table->dropColumn('ded_3');
            }
        });
    }
};

