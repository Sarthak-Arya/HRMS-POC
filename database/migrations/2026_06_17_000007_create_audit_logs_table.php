<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('company')->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event_type', 30);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->string('request_id', 64)->nullable();
            $table->string('source', 50)->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'changed_at']);
            $table->index(['event_type', 'changed_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `audit_logs` ADD CONSTRAINT `chk_audit_logs_event_type` CHECK (`event_type` IN ('CREATE', 'UPDATE', 'DELETE', 'STATUS_CHANGE', 'CALCULATION'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
