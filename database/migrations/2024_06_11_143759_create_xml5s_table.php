<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml5sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml5s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_lk', 100);
            $table->integer('stt')->nullable();
            $table->text('dien_bien');
            $table->text('hoi_chan')->nullable();
            $table->text('phau_thuat')->nullable();
            $table->string('ngay_yl', 12);
            $table->timestamps();

            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ngay_yl');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml5s');
    }
}