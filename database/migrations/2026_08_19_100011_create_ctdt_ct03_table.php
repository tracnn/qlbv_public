<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * CT03 - Giay ra vien (Mau so 02 - TT25).
 *
 * GHI_CHU co trong bang mo ta muc 9.2 nhung khong co trong XML mau muc 9.1 - van tao cot.
 * BENHICD10_ID / TENBENHICD10 dat ten KHAC cac loai khac (benh_icd10_id / benh_icd10_ten).
 * Giu nguyen theo dac ta, khong sua cho deu.
 */
class CreateCtdtCt03Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct03', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_khoa', 'ma_bhxh', 'ma_the', 'ho_ten',
                'ngay_sinh', 'gioi_tinh', 'ma_dantoc', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra', 'dinh_chi_thai_nghen', 'tuoi_thai',
                'thu_truong_dvi', 'ma_cchn_truongkhoa', 'ten_truongkhoa',
                'ngay_chung_tu', 'tekt', 'ho_ten_cha', 'ho_ten_me',
                'ngoaitru_tungay', 'ngoaitru_denngay',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benhicd10_id',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'ghi_chu', 'tenbenhicd10'];

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
        Schema::dropIfExists('ctdt_ct03');
    }
}
