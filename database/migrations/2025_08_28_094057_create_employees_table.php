<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('company_id')->constrained('company');
            $table->string('employee_code', 20)->unique();
            $table->string('employee_name', 200);
            $table->enum('gender', ['M', 'F', 'O']);
            $table->string('father_name', 200)->nullable();
            $table->foreignId('location_id')->constrained('locations');
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->date('dol')->nullable();
            $table->string('present_address_line1', 255)->nullable();
            $table->string('present_address_line2', 255)->nullable();
            $table->string('present_city', 100)->nullable();
            $table->string('present_state', 100)->nullable();
            $table->string('present_pincode', 20)->nullable();
            $table->string('present_country', 100)->nullable();
            $table->string('permanent_address_line1', 255)->nullable();
            $table->string('permanent_address_line2', 255)->nullable();
            $table->string('permanent_city', 100)->nullable();
            $table->string('permanent_state', 100)->nullable();
            $table->string('permanent_pincode', 20)->nullable();
            $table->string('permanent_country', 100)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('cca', 12, 2)->default(0);
            $table->decimal('da', 12, 2)->default(0);
            $table->string('pf_no', 20)->nullable();
            $table->string('esi_no', 20)->nullable();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('designation_id')->constrained('designations');
            $table->string('pay_mode', 20)->nullable();
            $table->string('pf_mode', 20)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('bank_account_no', 20)->nullable();
            $table->string('bank_ifsc_code', 20)->nullable();
            $table->string('bank_account_type', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_master');
    }
}
