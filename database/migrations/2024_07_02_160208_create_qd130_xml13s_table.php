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
            $table->string('so_hoso', 50);
            $table->string('so_chuyentuyen', 50);
            $table->string('giay_chuyen_tuyen', 50);
            $table->string('ma_cskcb', 5);
            $table->string('ma_noi_di', 5);
            $table->string('ma_noi_den', 5);
            $table->string('ho_ten', 255);
            $table->string('ngay_sinh', 12);
            $table->unsignedTinyInteger('gioi_tinh');
            $table->string('ma_quoctich', 3);
            $table->string('ma_dantoc', 2);
            $table->string('ma_nghe_nghiep', 5);
            $table->string('dia_chi', 1024);
            $table->string('ma_the_bhyt');
            $table->string('gt_the_den');
            $table->string('ngay_vao', 12);
            $table->string('ngay_vao_noi_tru', 12);
            $table->string('ngay_ra', 12);
            $table->text('dau_hieu_ls')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('qt_benhly')->nullable();
            $table->text('tomtat_kq')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ma_benh_chinh', 7);
            $table->string('ma_benh_kt', 100);
            $table->string('ma_benh_yhct', 255);
            $table->string('ten_dich_vu', 1024);
            $table->string('ten_thuoc', 1024);
            $table->unsignedTinyInteger('ma_loai_rv');
            $table->unsignedTinyInteger('ma_lydo_ct');
            $table->text('huong_dieu_tri')->nullable();
            $table->string('phuongtien_vc', 255)->nullable();
            $table->string('hoten_nguoi_ht', 255)->nullable();
            $table->string('chucdanh_nguoi_ht', 255)->nullable();
            $table->string('ma_bac_si', 255);
            $table->string('ma_ttdv', 10);
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
