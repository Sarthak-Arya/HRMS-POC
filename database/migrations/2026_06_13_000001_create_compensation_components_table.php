<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->string('component_name');
            $table->string('component_type', 20);
            $table->string('default_calculation_type', 20)->default('FIXED');
            $table->decimal('default_value', 12, 2)->nullable();
            $table->string('statutory_component', 10)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'component_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_components');
    }
};
