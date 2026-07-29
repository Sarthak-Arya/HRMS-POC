<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policy_leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('attendance_policies')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->decimal('annual_quota', 5, 2);
            $table->decimal('carry_forward_limit', 5, 2)->nullable();
            $table->boolean('encashable')->default(false);
            $table->timestamps();

            $table->unique(['policy_id', 'leave_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policy_leave_types');
    }
};
