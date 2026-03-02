<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('company_id')->constrained('company');
            $table->tinyInteger('month'); // 1-12
            $table->smallInteger('year');
            $table->decimal('casual_leave', 5, 2)->default(0);
            $table->decimal('earned_leave', 5, 2)->default(0);
            $table->decimal('sick_leave', 5, 2)->default(0);
            $table->decimal('holiday', 5, 2)->default(0);
            $table->decimal('worked_days', 5, 2)->default(0);
            $table->decimal('overtime_days', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('esi_la', 12, 2)->default(0);
            $table->decimal('total_days', 5, 2)->default(0);
            $table->decimal('prev_leave_days', 5, 2)->default(0);
            $table->decimal('prev_leave_amount', 12, 2)->default(0);
            $table->string('shift_code', 20)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
}

