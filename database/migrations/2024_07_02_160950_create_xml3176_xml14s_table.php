<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml14sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml14s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('so_giayhen_kl')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->string('ho_ten')->nullable();
            $table->string('ngay_sinh')->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('dia_chi', 1024)->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('gt_the_den')->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_vao_noi_tru')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->string('ngay_hen_kl')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->string('ma_benh_chinh')->nullable();
            $table->string('ma_benh_kt')->nullable();
            $table->string('ma_benh_yhct')->nullable();
            $table->string('ma_doituong_kcb')->nullable();
            $table->string('ma_bac_si')->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->string('ngay_ct')->nullable();
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
        Schema::dropIfExists('xml3176_xml14s');
    }
}
