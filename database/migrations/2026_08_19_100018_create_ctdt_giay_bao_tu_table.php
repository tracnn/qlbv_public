<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** GIAYBAOTU - dich vu loaiHs=60, goi HSDLGBT. Khoa nghiep vu la MA_GBT. */
class CreateCtdtGiayBaoTuTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_giay_bao_tu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_gbt', 'ma_bn', 'ma_hsba', 'ho_ten', 'ngay_sinh', 'gioi_tinh',
                'ma_the', 'ma_dantoc', 'ma_quoctich',
                'matinh_thuongtru', 'mahuyen_thuongtru', 'maxa_thuongtru',
                'matinh_hientai', 'mahuyen_hientai', 'maxa_hientai',
                'loai_giayto', 'so_giayto', 'ngay_cap', 'noi_cap',
                'ngaygio_vv', 'ngay_tv', 'tinh_trang_tv',
                'nguoi_ghigiay', 'nguoi_thanthich', 'ttruong_dvi',
                'so_baotu', 'quyen_so', 'ngay_capgiaybt', 'so_baotu_bd', 'quyen_so_bd',
                'macskcb', 'ma_bhxh', 'benh_icd10_id',
            ];

            $vanBan = [
                'dchi_thuongtru', 'dchi_hientai', 'nguyennhan_tv',
                'diachi_cskcb', 'benh_icd10_ten',
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
        Schema::dropIfExists('ctdt_giay_bao_tu');
    }
}
