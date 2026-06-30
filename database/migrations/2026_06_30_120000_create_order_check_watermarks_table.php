<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckWatermarksTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_watermarks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_key', 100)->unique();
            $table->unsignedBigInteger('last_create_time')->default(0);
            $table->unsignedBigInteger('last_modify_time')->default(0);
            $table->unsignedBigInteger('last_id')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_watermarks');
    }
}
