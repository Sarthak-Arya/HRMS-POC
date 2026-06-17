<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'month', 'year']);
            $table->index(['company_id', 'status']);
            $table->index(['year', 'month']);
        });

        $this->addStatusCheck('payroll_runs', 'status', [
            'DRAFT', 'PROCESSING', 'COMPLETED', 'LOCKED',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }

    /**
     * @param array<int, string> $values
     */
    private function addStatusCheck(string $table, string $column, array $values): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $allowed = implode("', '", $values);
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `chk_{$table}_{$column}` CHECK (`{$column}` IN ('{$allowed}'))");
    }
};
