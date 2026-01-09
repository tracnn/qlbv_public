<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml5sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml5s', function (Blueprint $table) {
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

            // Adding index
            $table->unique(['ma_lk', 'stt'], 'xml3176_xml5s_ma_lk_stt_unique');
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
        Schema::dropIfExists('xml3176_xml5s');
    }
}
