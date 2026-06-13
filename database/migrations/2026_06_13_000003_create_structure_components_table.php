<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('compensation_structures')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('compensation_components')->cascadeOnDelete();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('calculation_type', 20)->nullable();
            $table->text('formula_expression')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['structure_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_components');
    }
};
