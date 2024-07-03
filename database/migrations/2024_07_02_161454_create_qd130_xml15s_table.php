<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml15sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml15s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->unsignedInteger('stt')->nullable();
            $table->string('ma_bn', 100)->nullable();
            $table->string('ho_ten', 255)->nullable();
            $table->string('so_cccd')->nullable();
            $table->unsignedTinyInteger('phanloai_lao_vitri')->nullable();
            $table->unsignedTinyInteger('phanloai_lao_ts')->nullable();
            $table->unsignedTinyInteger('phanloai_lao_hiv')->nullable();
            $table->unsignedTinyInteger('phanloai_lao_vk')->nullable();
            $table->unsignedTinyInteger('phanloai_lao_kt')->nullable();
            $table->unsignedTinyInteger('loai_dtri_lao')->nullable();
            $table->string('ngaybd_dtri_lao', 8)->nullable();
            $table->unsignedTinyInteger('phacdo_dtri_lao')->nullable();
            $table->string('ngaykt_dtri_lao', 8)->nullable();
            $table->unsignedTinyInteger('ket_qua_dtri_lao')->nullable();
            $table->string('ma_cskcb', 5)->nullable();
            $table->string('ngaykd_hiv', 8)->nullable();
            $table->string('bddt_arv', 8)->nullable();
            $table->string('ngay_bat_dau_dt_ctx', 8)->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qd130_xml15s');
    }
}
