<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDutyPositionsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_duty_positions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_duty_positions');
    }
}
