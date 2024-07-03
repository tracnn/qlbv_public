<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml13sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml13s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('so_hoso', 50)->nullable();
            $table->string('so_chuyentuyen', 50)->nullable();
            $table->string('giay_chuyen_tuyen', 50)->nullable();
            $table->string('ma_cskcb', 5)->nullable();
            $table->string('ma_noi_di', 5)->nullable();
            $table->string('ma_noi_den', 5)->nullable();
            $table->string('ho_ten', 255)->nullable();
            $table->string('ngay_sinh', 12)->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('ma_quoctich', 3)->nullable();
            $table->string('ma_dantoc', 2)->nullable();
            $table->string('ma_nghe_nghiep', 5)->nullable();
            $table->string('dia_chi', 1024)->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('gt_the_den')->nullable();
            $table->string('ngay_vao', 12)->nullable();
            $table->string('ngay_vao_noi_tru', 12)->nullable();
            $table->string('ngay_ra', 12)->nullable();
            $table->text('dau_hieu_ls')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('qt_benhly')->nullable();
            $table->text('tomtat_kq')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ma_benh_chinh', 7)->nullable();
            $table->string('ma_benh_kt', 100)->nullable();
            $table->string('ma_benh_yhct', 255)->nullable();
            $table->string('ten_dich_vu', 1024)->nullable();
            $table->string('ten_thuoc', 1024)->nullable();
            $table->unsignedTinyInteger('ma_loai_rv')->nullable();
            $table->unsignedTinyInteger('ma_lydo_ct')->nullable();
            $table->text('huong_dieu_tri')->nullable();
            $table->string('phuongtien_vc', 255)->nullable();
            $table->string('hoten_nguoi_ht', 255)->nullable();
            $table->string('chucdanh_nguoi_ht', 255)->nullable();
            $table->string('ma_bac_si', 255)->nullable();
            $table->string('ma_ttdv', 10)->nullable();
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
        Schema::dropIfExists('qd130_xml13s');
    }
}
