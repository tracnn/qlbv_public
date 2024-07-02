<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml6sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml6s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('ma_the_bhyt');
            $table->string('so_cccd', 15);
            $table->string('ngay_sinh', 12);
            $table->unsignedTinyInteger('gioi_tinh');
            $table->string('dia_chi', 1024);
            $table->string('matinh_cu_tru', 3);
            $table->string('mahuyen_cu_tru', 3);
            $table->string('maxa_cu_tru', 5);
            $table->string('ngaykd_hiv', 8)->nullable();
            $table->string('noi_lay_mau_xn', 5);
            $table->string('noi_xn_kd', 5);
            $table->string('noi_bddt_arv', 5);
            $table->string('bddt_arv', 8)->nullable();
            $table->string('ma_phac_do_dieu_tri_bd', 200)->nullable();
            $table->unsignedTinyInteger('ma_bac_phac_do_bd')->nullable();
            $table->unsignedTinyInteger('ma_lydo_dtri')->nullable();
            $table->unsignedTinyInteger('loai_dtri_lao')->nullable();
            $table->unsignedTinyInteger('sang_loc_lao')->nullable();
            $table->unsignedTinyInteger('phacdo_dtri_lao')->nullable();
            $table->string('ngaybd_dtri_lao', 8)->nullable();
            $table->string('ngaykt_dtri_lao', 8)->nullable();
            $table->unsignedTinyInteger('kq_dtri_lao')->nullable();
            $table->unsignedTinyInteger('ma_lydo_xntl_vr')->nullable();
            $table->string('ngay_xn_tlvr', 8)->nullable();
            $table->unsignedTinyInteger('kq_xntl_vr')->nullable();
            $table->string('ngay_kq_xn_tlvr', 8)->nullable();
            $table->unsignedTinyInteger('ma_loai_bn')->nullable();
            $table->unsignedTinyInteger('giai_doan_lam_sang')->nullable();
            $table->unsignedSmallInteger('nhom_doi_tuong')->nullable();
            $table->string('ma_tinh_trang_dk', 18)->nullable();
            $table->unsignedTinyInteger('lan_xn_pcr')->nullable();
            $table->string('ngay_xn_pcr', 8)->nullable();
            $table->string('ngay_kq_xn_pcr', 8)->nullable();
            $table->unsignedTinyInteger('ma_kq_xn_pcr')->nullable();
            $table->string('ngay_nhan_tt_mang_thai', 8)->nullable();
            $table->string('ngay_bat_dau_dt_ctx', 8)->nullable();
            $table->string('ma_xu_tri', 10)->nullable();
            $table->string('ngay_bat_dau_xu_tri', 8)->nullable();
            $table->string('ngay_ket_thuc_xu_tri', 8)->nullable();
            $table->string('ma_phac_do_dieu_tri', 200)->nullable();
            $table->unsignedTinyInteger('ma_bac_phac_do')->nullable();
            $table->unsignedSmallInteger('so_ngay_cap_thuoc_arv')->nullable();
            $table->string('ngay_chuyen_phac_do', 8)->nullable();
            $table->unsignedTinyInteger('ly_do_chuyen_phac_do')->nullable();
            $table->string('ma_cskcb', 5);
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
        Schema::dropIfExists('qd130_xml6s');
    }
}
