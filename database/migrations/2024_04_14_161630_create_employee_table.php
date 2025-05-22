<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('employee_company_code')->nullable();
            $table->string('employee_first_name');
            $table->string('employee_middle_name')->nullable();
            $table->string('employee_last_name');
            $table->string('employee_esi_no')->nullable();
            $table->string('employee_pf_no')->nullable();
            $table->string('employee_father_name');
            $table->string('employee_gender');
            $table->date('employee_dob');
            $table->integer('employee_age');
            $table->date('employee_joining_date');
            $table->date('employee_leaving_date')->nullable();
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
