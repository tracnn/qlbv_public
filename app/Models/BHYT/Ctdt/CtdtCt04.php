<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtCt04 extends Model
{
    protected $table = 'ctdt_ct04';

    protected $fillable = [
        'chung_tu_id',
        'ma_ct', 'so_seri', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
        'gioi_tinh', 'ma_dantoc', 'nghe_nghiep', 'ho_ten_cha', 'ho_ten_me',
        'nguoi_giam_ho', 'ten_donvi', 'nguoi_dai_dien',
        'ngay_ct', 'ngay_vao', 'ngay_ra',
        'ngay_sinhcon', 'ngay_chetcon', 'so_conchet',
        'tekt', 'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'is_noi_khoa', 'is_phau_thuat_thu_thuat', 'benh_icd10_id',
        'is_lao_giai_doan_nang', 'is_xo_gan_giai_doan_mat_bu',
        'dia_chi', 'chan_doan_vao', 'chan_doan_ra', 'qt_benhly', 'tomtat_kq',
        'pp_dieutri', 'tt_ravien', 'ghi_chu', 'lydo_vvien', 'tien_su_benh',
        'dau_hieu_lam_sang', 'noi_khoa', 'phau_thuat_thu_thuat',
        'huong_dieu_tri', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
