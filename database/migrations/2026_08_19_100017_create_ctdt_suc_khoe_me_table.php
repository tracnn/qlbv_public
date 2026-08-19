<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYSUCKHOEME - Giay xac nhan nguoi me khong du suc khoe cham soc con (Mau so 10 - TT25).
 * The goc trong base64 la <CTGiaySucKhoeMe>, KHAC gia tri LOAIHOSO.
 */
class CreateCtdtSucKhoeMeTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_suc_khoe_me', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'ma_khoa', 'ma_tinhcutru', 'ma_xacutru', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri',
            ];

            $vanBan = [
                'dia_chi', 'chan_doan', 'pp_dieutri', 'ket_luan',
                'tinhtrangbenhhientai', 'benh_icd10_ten',
            ];

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
        Schema::dropIfExists('ctdt_suc_khoe_me');
    }
}
