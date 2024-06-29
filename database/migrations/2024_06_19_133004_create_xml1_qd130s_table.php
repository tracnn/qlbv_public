<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml1Qd130sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml1s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->string('ma_bn')->nullable();
            $table->string('ho_ten')->nullable();
            $table->string('so_cccd')->nullable();
            $table->string('ngay_sinh')->nullable();
            $table->integer('gioi_tinh')->nullable();
            $table->string('nhom_mau')->nullable();
            $table->string('ma_quoctich')->nullable();
            $table->string('ma_dantoc')->nullable();
            $table->string('ma_nghe_nghiep')->nullable();
            $table->text('dia_chi')->nullable();
            $table->string('matinh_cu_tru')->nullable();
            $table->string('mahuyen_cu_tru')->nullable();
            $table->string('maxa_cu_tru')->nullable();
            $table->string('dien_thoai')->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('ma_dkbd')->nullable();
            $table->string('gt_the_tu')->nullable();
            $table->string('gt_the_den')->nullable();
            $table->string('ngay_mien_cct')->nullable();
            $table->text('ly_do_vv')->nullable();
            $table->text('ly_do_vnt')->nullable();
            $table->string('ma_ly_do_vnt')->nullable();
            $table->text('chan_doan_vao')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->string('ma_benh_chinh')->nullable();
            $table->text('ma_benh_kt')->nullable();
            $table->string('ma_benh_yhct')->nullable();
            $table->string('ma_pttt_qt')->nullable();
            $table->string('ma_doituong_kcb')->nullable();
            $table->string('ma_noi_di')->nullable();
            $table->string('ma_noi_den')->nullable();
            $table->string('ma_tai_nan')->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_vao_noi_tru')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->string('giay_chuyen_tuyen')->nullable();
            $table->integer('so_ngay_dtri')->nullable();
            $table->text('pp_dieu_tri')->nullable();
            $table->integer('ket_qua_dtri')->nullable();
            $table->integer('ma_loai_rv')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('ngay_ttoan')->nullable();
            $table->double('t_thuoc')->nullable();
            $table->double('t_vtyt')->nullable();
            $table->double('t_tongchi_bv')->nullable();
            $table->double('t_tongchi_bh')->nullable();
            $table->double('t_bntt')->nullable();
            $table->double('t_bncct')->nullable();
            $table->double('t_bhtt')->nullable();
            $table->double('t_nguonkhac')->nullable();
            $table->double('t_bhtt_gdv')->nullable();
            $table->string('nam_qt')->nullable();
            $table->string('thang_qt')->nullable();
            $table->string('ma_loai_kcb')->nullable();
            $table->string('ma_khoa')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->string('ma_khuvuc')->nullable();
            $table->string('can_nang')->nullable();
            $table->string('can_nang_con')->nullable();
            $table->string('nam_nam_lien_tuc')->nullable();
            $table->string('ngay_tai_kham')->nullable();
            $table->string('ma_hsba')->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();

            //Adding index
            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ma_bn');
            $table->index('ma_the_bhyt');
            $table->index('ngay_vao');
            $table->index('ngay_ra');
            $table->index('ngay_ttoan');
            $table->index('ma_loai_kcb');
            $table->index('ma_khoa');
            $table->index('ngay_tai_kham');
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
        Schema::dropIfExists('qd130_xml1s');
    }
}
