<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRefServiceRestrictionTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_ref_service_restriction', function (Blueprint $table) {
            $table->increments('id');
            $table->string('service_code', 50)->unique();
            $table->string('service_name', 255)->nullable();
            $table->unsignedTinyInteger('required_gender_id')->nullable(); // 1=Nu,2=Nam; null=khong gioi han
            $table->unsignedSmallInteger('age_from')->nullable();          // tuoi nho nhat (nam)
            $table->unsignedSmallInteger('age_to')->nullable();            // tuoi lon nhat (nam)
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_ref_service_restriction');
    }
}
