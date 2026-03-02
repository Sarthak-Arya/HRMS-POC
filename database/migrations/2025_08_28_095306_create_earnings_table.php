<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEarningsTable extends Migration
{
    public function up()
    {
        Schema::create('earnings', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('company_id')->constrained('company');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('payroll_id')->nullable()->constrained('payroll_header');
            $table->string('earning_type', 50);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('earnings');
    }
}

