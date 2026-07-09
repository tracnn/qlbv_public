<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDutyEditorsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_duty_editors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique(); // acs_user.id
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_duty_editors');
    }
}
