<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT04 - Ban tom tat ho so benh an (Mau so 03 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt04Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct04', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_ct', 'so_seri', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'gioi_tinh', 'ma_dantoc', 'nghe_nghiep', 'ho_ten_cha', 'ho_ten_me',
                'nguoi_giam_ho', 'ten_donvi', 'nguoi_dai_dien',
                'ngay_ct', 'ngay_vao', 'ngay_ra',
                'ngay_sinhcon', 'ngay_chetcon', 'so_conchet',
                'tekt', 'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'is_noi_khoa', 'is_phau_thuat_thu_thuat', 'benh_icd10_id',
                'is_lao_giai_doan_nang', 'is_xo_gan_giai_doan_mat_bu',
            ];

            $vanBan = [
                'dia_chi', 'chan_doan_vao', 'chan_doan_ra', 'qt_benhly', 'tomtat_kq',
                'pp_dieutri', 'tt_ravien', 'ghi_chu', 'lydo_vvien', 'tien_su_benh',
                'dau_hieu_lam_sang', 'noi_khoa', 'phau_thuat_thu_thuat',
                'huong_dieu_tri', 'benh_icd10_ten',
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
        Schema::dropIfExists('ctdt_ct04');
    }
}
