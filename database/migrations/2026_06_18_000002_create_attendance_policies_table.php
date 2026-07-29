<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company')->cascadeOnDelete();
            $table->string('policy_name', 100);
            $table->string('attendance_mode', 20);
            $table->string('working_days_basis', 20)->default('fixed_26');
            $table->decimal('custom_working_days', 5, 2)->nullable();
            $table->string('weekly_off_rule', 20)->default('sunday');
            $table->json('custom_weekly_off_days')->nullable();
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->unsignedSmallInteger('min_half_day_minutes')->default(240);
            $table->unsignedSmallInteger('min_full_day_minutes')->default(480);
            $table->boolean('allow_overtime')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('allow_negative_leave_balance')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'policy_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
