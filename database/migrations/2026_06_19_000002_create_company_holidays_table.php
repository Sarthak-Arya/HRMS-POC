<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('name', 150);
            $table->string('source_type', 20)->default('company');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('region_code', 50)->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'holiday_date']);
            $table->index(['company_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};
