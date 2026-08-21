<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYDIEUTRINOITRU - Giay xac nhan qua trinh dieu tri noi tru (Mau so 06 - TT25).
 *
 * The goc trong base64 la <CTGiayDieuTriNoiTru>, KHAC gia tri LOAIHOSO.
 * MA_DAN_TOC co gach duoi giua DAN va TOC, khac CT03 (MA_DANTOC).
 * khong phai ID. Giu nguyen.
 */
class CreateCtdtDieuTriNoiTruTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_dieu_tri_noi_tru', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'gioi_tinh', 'ma_khoa', 'ten_dan_toc', 'ma_dan_toc', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_id', 'ma_ct', 'ngay_ct', 'so_seri', 'tuoi_thai',
                'loai_phuong_phap', 'loai_pp_dieu_tri_vosinh',
                'ngay_dinh_chi_thainghen', 'is_nghiduongthai', 'so_ngay_nghiduongthai',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'mo_ta', 'ghi_chu', 'benh_icd10_ten'];

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
        Schema::dropIfExists('ctdt_dieu_tri_noi_tru');
    }
}
