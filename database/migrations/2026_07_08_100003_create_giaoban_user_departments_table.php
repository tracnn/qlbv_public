<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanUserDepartmentsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_user_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('dept_config_id');
            $table->timestamps();
            $table->unique(['user_id', 'dept_config_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_user_departments');
    }
}
