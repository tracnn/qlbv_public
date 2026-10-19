<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Mot BAN ho so tong hop 01/BH = mot dong = mot don vi ky / gui.
 *
 * VI SAO bang rieng, khong dung tt12_ho_so: tt12_ho_so gan voi luong nap Excel va dong bo
 * sang bang danh muc; 01/BH lot vao do se bi coi la danh muc. Khoa nghiep vu cung khac
 * (co so + ky + so ban).
 *
 * Cot tien dung decimal(18,2): tong tien can cong/doi chieu chinh xac, khong chap nhan sai so
 * dau phay dong cua double.
 */
class CreateBh01BanTable extends Migration
{
    public function up()
    {
        Schema::create('bh01_ban', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ma_ban', 100)->unique();     // vd BH01_01929_202610_02

            $table->string('ma_cskcb', 5);
            $table->string('nam_qt', 4);
            $table->string('thang_qt', 2);               // giu so 0 dau: '09'
            $table->integer('so_ban');
            $table->integer('so_dong')->default(0);

            $table->decimal('t_tongchi_bv', 18, 2)->default(0);
            $table->decimal('t_tongchi_bh', 18, 2)->default(0);
            $table->decimal('t_bhtt', 18, 2)->default(0);
            $table->decimal('t_bncct', 18, 2)->default(0);
            $table->decimal('t_bntt', 18, 2)->default(0);
            $table->decimal('t_nguonkhac', 18, 2)->default(0);

            $table->integer('so_canh_bao')->default(0);

            // GUID thuoc tinh Id cua the DANHSACH_CHITIET: sinh MOT LAN luc dung ban, vi chu ky
            // XMLDSig tham chieu chinh #Id nay.
            $table->string('id_ds_chitiet', 64)->nullable();

            $table->string('tao_boi')->nullable();
            $table->timestamp('tao_luc')->nullable();

            // Ky: cho_ky | da_ky | ky_loi
            $table->string('trang_thai_ky', 20)->default('cho_ky');
            $table->string('sign_method')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('signed_error')->nullable();
            $table->string('duong_dan_da_ky')->nullable();
            $table->string('sha256_tep', 64)->nullable();

            // Gui gan nhat (lich su day du nam o bh01_lich_su_gui).
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_by')->nullable();
            $table->string('ma_ket_qua', 10)->nullable();
            $table->string('ma_gd', 100)->nullable();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();
            $table->text('thong_diep')->nullable();

            // Khoa chong gui trung: co gia tri nghia la mot job dang xu ly ban nay.
            $table->timestamp('dang_xu_ly_tu')->nullable();

            $table->timestamps();

            $table->unique(['ma_cskcb', 'nam_qt', 'thang_qt', 'so_ban'], 'uq_bh01_ban_ky_so');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bh01_ban');
    }
}
