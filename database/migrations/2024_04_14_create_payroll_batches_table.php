<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('designation_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('processing'); // processing, completed, failed
            $table->integer('total_jobs')->default(0);
            $table->integer('processed_jobs')->default(0);
            $table->json('failed_jobs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_batches');
    }
}; 