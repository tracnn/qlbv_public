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
            $table->unsignedInteger('user_id')->nullable();
            $table->string('person_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
            $table->unique(['report_id', 'position_id'], 'giaoban_duty_unique');
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_duties');
    }
}
