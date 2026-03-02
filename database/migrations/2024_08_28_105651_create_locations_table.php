<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('location_name');
            $table->string('location_code');
            $table->string('location_address');
            $table->string('location_city');
            $table->string('location_state');
            $table->string('location_pincode');
            $table->string('location_country');
            $table->string('location_phone');
            $table->string('location_email');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
}
