<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml5Qd130sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml5s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->text('dien_bien_ls')->nullable();
            $table->text('giai_doan_benh')->nullable();
            $table->text('hoi_chan')->nullable();
            $table->text('phau_thuat')->nullable();
            $table->string('thoi_diem_dbls')->nullable();
            $table->string('nguoi_thuc_hien')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();

            //Adding index
            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('thoi_diem_dbls');
            $table->index('nguoi_thuc_hien');
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
        Schema::dropIfExists('qd130_xml5s');
    }
}
