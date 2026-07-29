<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('company')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('data_source');
            $table->json('config');
            $table->string('default_format')->default('xlsx');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'data_source', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_report_templates');
    }
};
