<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->foreignId('component_id')->constrained('compensation_components')->cascadeOnDelete();
            $table->string('override_type', 20);
            $table->decimal('value', 12, 2)->nullable();
            $table->string('calculation_type', 20)->nullable();
            $table->text('formula_expression')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['company_id', 'scope_type', 'scope_id', 'component_id', 'effective_from'],
                'comp_overrides_scope_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_overrides');
    }
};
