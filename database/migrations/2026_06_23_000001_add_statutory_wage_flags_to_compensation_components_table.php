<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_components', function (Blueprint $table) {
            if (! Schema::hasColumn('compensation_components', 'included_in_pf_wages')) {
                $table->boolean('included_in_pf_wages')->default(false)->after('statutory_component');
            }
            if (! Schema::hasColumn('compensation_components', 'included_in_esi_wages')) {
                $table->boolean('included_in_esi_wages')->default(false)->after('included_in_pf_wages');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compensation_components', function (Blueprint $table) {
            if (Schema::hasColumn('compensation_components', 'included_in_esi_wages')) {
                $table->dropColumn('included_in_esi_wages');
            }
            if (Schema::hasColumn('compensation_components', 'included_in_pf_wages')) {
                $table->dropColumn('included_in_pf_wages');
            }
        });
    }
};
