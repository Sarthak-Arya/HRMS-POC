<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_components', function (Blueprint $table) {
            if (! Schema::hasColumn('compensation_components', 'benefit_plan')) {
                $table->string('benefit_plan', 50)->nullable()->after('statutory_component');
            }
            if (! Schema::hasColumn('compensation_components', 'associate_investment')) {
                $table->string('associate_investment', 50)->nullable()->after('benefit_plan');
            }
            if (! Schema::hasColumn('compensation_components', 'include_employer_contribution')) {
                $table->boolean('include_employer_contribution')->default(false)->after('associate_investment');
            }
            if (! Schema::hasColumn('compensation_components', 'is_superannuation')) {
                $table->boolean('is_superannuation')->default(false)->after('include_employer_contribution');
            }
            if (! Schema::hasColumn('compensation_components', 'pro_rata_basis')) {
                $table->boolean('pro_rata_basis')->default(false)->after('is_superannuation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compensation_components', function (Blueprint $table) {
            if (Schema::hasColumn('compensation_components', 'pro_rata_basis')) {
                $table->dropColumn('pro_rata_basis');
            }
            if (Schema::hasColumn('compensation_components', 'is_superannuation')) {
                $table->dropColumn('is_superannuation');
            }
            if (Schema::hasColumn('compensation_components', 'include_employer_contribution')) {
                $table->dropColumn('include_employer_contribution');
            }
            if (Schema::hasColumn('compensation_components', 'associate_investment')) {
                $table->dropColumn('associate_investment');
            }
            if (Schema::hasColumn('compensation_components', 'benefit_plan')) {
                $table->dropColumn('benefit_plan');
            }
        });
    }
};
