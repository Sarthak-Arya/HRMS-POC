<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollHeaderTable extends Migration
{
    public function up()
    {
        Schema::create('payroll_header', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('attendance_id')->constrained('attendance');
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->date('pay_date');
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_header');
    }
}
