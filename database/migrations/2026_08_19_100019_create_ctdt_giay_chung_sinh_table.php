<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYCHUNGSINH - dich vu loaiHs=61, goi HSDLGCS. Khoa nghiep vu la MA_GCS.
 *
 * BA nhom nguoi dung hau to khac nhau, de nham nhat trong ca dac ta:
 *   _NND     : nguoi de
 *   _MTH     : me thay the (mang thai ho)
 *   _CHA_MTH : cha cua me thay the
 *   _CHA_NND : cha cua nguoi de
 *
 * NGAY_SINH_CON dai 14 ky tu (20251021000000), khac cac truong ngay 8 hoac 12 ky tu.
 */
class CreateCtdtGiayChungSinhTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_giay_chung_sinh', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                // Dinh danh va nguoi de
                'ma_gcs', 'ma_bn', 'ma_ct', 'so_seri',
                'ma_bhxh_nnd', 'ma_the_nnd', 'hoten_nnd', 'ngaysinh_nnd',
                'ma_dantoc_nnd', 'ma_quoctich_nnd', 'loai_giayto_nnd', 'so_cccd_nnd',
                'ngaycap_cccd_nnd', 'noicap_cccd_nnd',
                'matinh_cu_tru', 'mahuyen_cu_tru', 'maxa_cu_tru',
                'ho_ten_cha', 'ma_the_tam',
                // Thong tin con
                'ten_con', 'gioi_tinh_con', 'so_con', 'lan_sinh', 'so_con_song',
                'can_nang_con', 'ngay_sinh_con',
                'sinhcon_phauthuat', 'sinhcon_duoi32tuan',
                // Nguoi lap phieu va don vi
                'nguoi_do_de', 'nguoi_ghi_phieu', 'ma_ttdv', 'thu_truong_dvi',
                'ngay_ct', 'so', 'quyen_so',
                // Me thay the
                'ma_bhxh_mth', 'ma_the_mth', 'hoten_mth', 'ngaysinh_mth',
                'ma_dantoc_mth', 'ma_quoctich_mth', 'loai_giayto_mth', 'so_cccd_mth',
                'ngaycap_cccd_mth', 'noicap_cccd_mth',
                'matinh_cu_tru_mth', 'maxa_cu_tru_mth',
                'ho_ten_cha_mth', 'ngaysinh_cha_mth', 'ma_dantoc_cha_mth',
                'matinh_cu_tru_cha_mth', 'maxa_cu_tru_cha_mth',
                'loai_giayto_cha_mth', 'so_cccd_cha_mth',
                'ngaycap_cccd_cha_mth', 'noicap_cccd_cha_mth',
                // Cha cua nguoi de
                'ngaysinh_cha_nnd', 'ma_dantoc_cha_nnd', 'loai_giayto_cha_nnd',
                'so_cccd_cha_nnd', 'ngaycap_cccd_cha_nnd', 'noicap_cccd_cha_nnd',
                'cap_lan_dau',
            ];

            $vanBan = [
                'noi_cu_tru_nnd', 'noi_sinh_con', 'tinh_trang_con', 'ghi_chu',
                'noi_cu_tru_mth', 'noi_cu_tru_cha_mth',
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
        Schema::dropIfExists('ctdt_giay_chung_sinh');
    }
}
