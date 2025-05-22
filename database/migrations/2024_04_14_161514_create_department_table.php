<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentTable extends Migration
{
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('department_name');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
}
