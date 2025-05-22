<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date');
            $table->json('salary_json');
            $table->timestamps();

            $table->unique(['employee_id', 'from_date', 'to_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('salaries');
    }
}; 