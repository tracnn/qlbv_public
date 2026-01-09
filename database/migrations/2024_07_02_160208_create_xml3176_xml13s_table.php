<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml13sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml13s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('so_hoso')->nullable();
            $table->string('so_chuyentuyen')->nullable();
            $table->string('giay_chuyen_tuyen')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->string('ma_noi_di')->nullable();
            $table->string('ma_noi_den')->nullable();
            $table->string('ho_ten')->nullable();
            $table->string('ngay_sinh')->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('ma_quoctich')->nullable();
            $table->string('ma_dantoc')->nullable();
            $table->string('ma_nghe_nghiep')->nullable();
            $table->string('dia_chi', 1024)->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('gt_the_den')->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_vao_noi_tru')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->text('dau_hieu_ls')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('qt_benhly')->nullable();
            $table->text('tomtat_kq')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ma_benh_chinh')->nullable();
            $table->string('ma_benh_kt')->nullable();
            $table->string('ma_benh_yhct')->nullable();
            $table->string('ten_dich_vu', 1024)->nullable();
            $table->string('ten_thuoc', 1024)->nullable();
            $table->unsignedTinyInteger('ma_loai_rv')->nullable();
            $table->unsignedTinyInteger('ma_lydo_ct')->nullable();
            $table->text('huong_dieu_tri')->nullable();
            $table->string('phuongtien_vc')->nullable();
            $table->string('hoten_nguoi_ht')->nullable();
            $table->string('chucdanh_nguoi_ht')->nullable();
            $table->string('ma_bac_si')->nullable();
            $table->string('ma_ttdv')->nullable();
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
        Schema::dropIfExists('xml3176_xml13s');
    }
}
