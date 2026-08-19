<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYDIEUTRIVOSINH - Giay xac nhan qua trinh dieu tri vo sinh (Mau so 09 - TT25).
 * The goc trong base64 la <CTGiayDieuTriVoSinh>, KHAC gia tri LOAIHOSO.
 */
class CreateCtdtDieuTriVoSinhTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_dieu_tri_vo_sinh', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'ma_khoa', 'ma_tinhcutru', 'ma_xacutru', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri', 'loai_phuong_phap',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'ghi_chu', 'benh_icd10_ten'];

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
        Schema::dropIfExists('ctdt_dieu_tri_vo_sinh');
    }
}
