<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT07 - Giay chung nhan nghi viec huong BHXH (Mau so 07 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt07Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct07', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_ct', 'mau_so', 'so_seri', 'so_kcb', 'ma_bhxh', 'ma_the',
                'ho_ten', 'ngay_sinh', 'gioi_tinh',
                'tu_ngay', 'den_ngay', 'ho_ten_cha', 'ho_ten_me',
                'thu_truong_dv', 'ma_cchn', 'ten_nguoi_hanh_nghe',
                'ngay_chung_tu', 'tekt',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'ngay_kcb', 'benh_icd10_id',
            ];

            $vanBan = ['don_vi', 'chandoan_dieutri', 'benh_icd10_ten'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ct07');
    }
}
