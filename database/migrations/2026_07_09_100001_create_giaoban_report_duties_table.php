<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportDutiesTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_duties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('employee_id')->nullable(); // his_employee.id
            $table->string('person_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
            $table->index('report_id');
            $table->index(['report_id', 'position_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_duties');
    }
}
