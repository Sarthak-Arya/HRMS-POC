<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollDeductionsTable extends Migration
{
    public function up()
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('payroll_id')->constrained('payroll_header');
            $table->foreignId('deduction_type_id')->constrained('deductions');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_deductions');
    }
}
