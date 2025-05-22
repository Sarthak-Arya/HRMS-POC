<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('days');
            $table->integer('casual_leave')->default(0);
            $table->integer('earned_leave')->default(0);
            $table->integer('maternity_leave')->default(0);
            $table->json('earnings')->nullable();
            $table->json('deductions')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}; 