<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyTable extends Migration
{
    public function up()
    {
        Schema::create('company', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->text('company_address');
            $table->boolean('is_esi');
            $table->boolean('is_pf');
            $table->unsignedBigInteger('company_handled_by');
            $table->timestamps();

            $table->foreign('company_handled_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company');
    }
}


