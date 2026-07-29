<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('section', 64);
            $table->json('before_json')->nullable();
            $table->json('after_json');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings_audit_logs');
    }
};
