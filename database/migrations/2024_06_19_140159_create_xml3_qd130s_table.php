<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3Qd130sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml3s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk');
            $table->integer('stt');
            $table->string('ma_dich_vu')->nullable();
            $table->string('ma_pttt_qt')->nullable();
            $table->string('ma_vat_tu')->nullable();
            $table->string('ma_nhom')->nullable();
            $table->string('goi_vtyt')->nullable();
            $table->string('ten_vat_tu')->nullable();
            $table->string('ten_dich_vu')->nullable();
            $table->string('ma_xang_dau')->nullable();
            $table->string('don_vi_tinh')->nullable();
            $table->integer('pham_vi')->nullable();
            $table->double('so_luong')->nullable();
            $table->double('don_gia_bv')->nullable();
            $table->double('don_gia_bh')->nullable();
            $table->string('tt_thau')->nullable();
            $table->double('tyle_tt_dv')->nullable();
            $table->double('tyle_tt_bh')->nullable();
            $table->double('thanh_tien_bv')->nullable();
            $table->double('thanh_tien_bh')->nullable();
            $table->double('t_trantt')->nullable();
            $table->double('muc_huong')->nullable();
            $table->double('t_nguonkhac_nsnn')->nullable();
            $table->double('t_nguonkhac_vtnn')->nullable();
            $table->double('t_nguonkhac_vttn')->nullable();
            $table->double('t_nguonkhac_cl')->nullable();
            $table->double('t_nguonkhac')->nullable();
            $table->double('t_bhtt')->nullable();
            $table->double('t_bntt')->nullable();
            $table->double('t_bncct')->nullable();
            $table->string('ma_khoa')->nullable();
            $table->string('ma_giuong')->nullable();
            $table->string('ma_bac_si')->nullable();
            $table->string('nguoi_thuc_hien')->nullable();
            $table->string('ma_benh')->nullable();
            $table->string('ma_benh_yhct')->nullable();
            $table->string('ngay_yl')->nullable();
            $table->string('ngay_th_yl')->nullable();
            $table->string('ngay_kq')->nullable();
            $table->string('ma_pttt')->nullable();
            $table->string('vet_thuong_tp')->nullable();
            $table->string('pp_vo_cam')->nullable();
            $table->string('vi_tri_th_dvkt')->nullable();
            $table->string('ma_may')->nullable();
            $table->string('ma_hieu_sp')->nullable();
            $table->string('tai_su_dung')->nullable();
            $table->text('du_phong')->nullable();
            $table->timestamps();

            //Adding index
            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ma_dich_vu');
            $table->index('ma_vat_tu');
            $table->index('ma_nhom');
            $table->index('ma_bac_si');
            $table->index('ngay_yl');
            $table->index('ngay_th_yl');
            $table->index('ngay_kq');
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
        Schema::dropIfExists('qd130_xml3s');
    }
}
