<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml8sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml8s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('ma_loai_kcb')->nullable();
            $table->string('ho_ten_cha')->nullable();
            $table->string('ho_ten_me')->nullable();
            $table->string('nguoi_giam_ho')->nullable();
            $table->string('don_vi', 1024)->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->text('chan_doan_vao')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('qt_benhly')->nullable();
            $table->text('tomtat_kq')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ngay_sinhcon')->nullable();
            $table->string('ngay_conchet')->nullable();
            $table->unsignedTinyInteger('so_conchet')->nullable();
            $table->unsignedTinyInteger('ket_qua_dtri')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->string('ngay_ct')->nullable();
            $table->string('ma_the_tam')->nullable();
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
        Schema::dropIfExists('xml3176_xml8s');
    }
}
