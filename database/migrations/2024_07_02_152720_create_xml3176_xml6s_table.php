<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml6sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml6s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('so_cccd')->nullable();
            $table->string('ngay_sinh')->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('dia_chi', 1024)->nullable();
            $table->string('matinh_cu_tru')->nullable();
            $table->string('mahuyen_cu_tru')->nullable();
            $table->string('maxa_cu_tru')->nullable();
            $table->string('ngaykd_hiv')->nullable();
            $table->string('noi_lay_mau_xn')->nullable();
            $table->string('noi_xn_kd')->nullable();
            $table->string('noi_bddt_arv')->nullable();
            $table->string('bddt_arv')->nullable();
            $table->string('ma_phac_do_dieu_tri_bd')->nullable();
            $table->unsignedTinyInteger('ma_bac_phac_do_bd')->nullable();
            $table->unsignedTinyInteger('ma_lydo_dtri')->nullable();
            $table->unsignedTinyInteger('loai_dtri_lao')->nullable();
            $table->unsignedTinyInteger('sang_loc_lao')->nullable();
            $table->unsignedTinyInteger('phacdo_dtri_lao')->nullable();
            $table->string('ngaybd_dtri_lao')->nullable();
            $table->string('ngaykt_dtri_lao')->nullable();
            $table->unsignedTinyInteger('kq_dtri_lao')->nullable();
            $table->unsignedTinyInteger('ma_lydo_xntl_vr')->nullable();
            $table->string('ngay_xn_tlvr')->nullable();
            $table->unsignedTinyInteger('kq_xntl_vr')->nullable();
            $table->string('ngay_kq_xn_tlvr')->nullable();
            $table->unsignedTinyInteger('ma_loai_bn')->nullable();
            $table->unsignedTinyInteger('giai_doan_lam_sang')->nullable();
            $table->unsignedSmallInteger('nhom_doi_tuong')->nullable();
            $table->string('ma_tinh_trang_dk')->nullable();
            $table->unsignedTinyInteger('lan_xn_pcr')->nullable();
            $table->string('ngay_xn_pcr')->nullable();
            $table->string('ngay_kq_xn_pcr')->nullable();
            $table->unsignedTinyInteger('ma_kq_xn_pcr')->nullable();
            $table->string('ngay_nhan_tt_mang_thai')->nullable();
            $table->string('ngay_bat_dau_dt_ctx')->nullable();
            $table->string('ma_xu_tri')->nullable();
            $table->string('ngay_bat_dau_xu_tri')->nullable();
            $table->string('ngay_ket_thuc_xu_tri')->nullable();
            $table->string('ma_phac_do_dieu_tri')->nullable();
            $table->unsignedTinyInteger('ma_bac_phac_do')->nullable();
            $table->unsignedSmallInteger('so_ngay_cap_thuoc_arv')->nullable();
            $table->string('ngay_chuyen_phac_do')->nullable();
            $table->unsignedTinyInteger('ly_do_chuyen_phac_do')->nullable();
            $table->string('ma_cskcb')->nullable();
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
        Schema::dropIfExists('xml3176_xml6s');
    }
}
