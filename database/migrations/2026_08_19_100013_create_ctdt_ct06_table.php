<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT06 - Giay xac nhan nghi duong thai (Mau so 11 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt06Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct06', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra',
                'nguoi_dai_dien', 'ma_bs', 'ten_bs', 'ten_dvi', 'so_kcb',
                'ngay_ct', 'so_seri', 'ma_ct',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd',
                'matinh_cu_tru', 'maxa_cu_tru', 'tuoi_thai', 'benh_icd10_id',
            ];

            $vanBan = ['chan_doan', 'noi_cu_tru_nnd', 'benh_icd10_ten'];

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
        Schema::dropIfExists('ctdt_ct06');
    }
}
