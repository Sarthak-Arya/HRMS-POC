<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_firms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('b2b_firm_id')
                ->nullable()
                ->after('id')
                ->constrained('b2b_firms')
                ->nullOnDelete();

            $table->unsignedBigInteger('company_id')->nullable()->after('b2b_firm_id');
        });

        Schema::table('company', function (Blueprint $table) {
            $table->foreignId('b2b_firm_id')
                ->nullable()
                ->after('id')
                ->constrained('b2b_firms')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')
                ->on('company')
                ->nullOnDelete();
        });

        $this->migrateLegacyCompanyOwnership();

        Schema::table('company', function (Blueprint $table) {
            $table->dropForeign(['company_handled_by']);
            $table->dropColumn('company_handled_by');
        });
    }

    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->unsignedBigInteger('company_handled_by')->nullable()->after('is_pf');
        });

        $companies = DB::table('company')->select('id', 'b2b_firm_id')->get();
        foreach ($companies as $company) {
            $handlerId = null;

            if ($company->b2b_firm_id) {
                $handlerId = DB::table('users')
                    ->where('b2b_firm_id', $company->b2b_firm_id)
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $handlerId) {
                $handlerId = DB::table('users')
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $handlerId) {
                $handlerId = DB::table('users')->orderBy('id')->value('id');
            }

            if ($handlerId) {
                DB::table('company')
                    ->where('id', $company->id)
                    ->update(['company_handled_by' => $handlerId]);
            }
        }

        Schema::table('company', function (Blueprint $table) {
            $table->foreign('company_handled_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['b2b_firm_id']);
            $table->dropColumn(['company_id', 'b2b_firm_id']);
        });

        Schema::table('company', function (Blueprint $table) {
            $table->dropForeign(['b2b_firm_id']);
            $table->dropColumn('b2b_firm_id');
        });

        Schema::dropIfExists('b2b_firms');
    }

    private function migrateLegacyCompanyOwnership(): void
    {
        if (! Schema::hasColumn('company', 'company_handled_by')) {
            return;
        }

        $handlerIds = DB::table('company')
            ->whereNotNull('company_handled_by')
            ->distinct()
            ->pluck('company_handled_by');

        foreach ($handlerIds as $handlerId) {
            $user = DB::table('users')->where('id', $handlerId)->first();
            if (! $user) {
                continue;
            }

            $companyCount = DB::table('company')
                ->where('company_handled_by', $handlerId)
                ->count();

            $companies = DB::table('company')
                ->where('company_handled_by', $handlerId)
                ->orderBy('id')
                ->get(['id', 'company_name']);

            if ($companyCount > 1) {
                $firmId = DB::table('b2b_firms')->insertGetId([
                    'name' => trim((string) $user->name) !== ''
                        ? $user->name.' Firm'
                        : 'Firm '.$handlerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')
                    ->where('id', $handlerId)
                    ->update(['b2b_firm_id' => $firmId]);

                DB::table('company')
                    ->where('company_handled_by', $handlerId)
                    ->update(['b2b_firm_id' => $firmId]);

                continue;
            }

            // Single-company handlers become B2C users linked directly to that company.
            $company = $companies->first();
            if ($company) {
                DB::table('users')
                    ->where('id', $handlerId)
                    ->update(['company_id' => $company->id]);
            }
        }
    }
};
