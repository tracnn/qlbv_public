<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtDieuTriNoiTru extends Model
{
    protected $table = 'ctdt_dieu_tri_noi_tru';

    protected $fillable = [
        'chung_tu_id',
        'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
        'gioi_tinh', 'ma_khoa', 'ten_dan_toc', 'ma_dan_toc', 'nghe_nghiep',
        'ngay_vao', 'ngay_ra',
        'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri', 'tuoi_thai',
        'loai_phuong_phap', 'loai_pp_dieu_tri_vosinh',
        'ngay_dinh_chi_thainghen', 'is_nghiduongthai', 'so_ngay_nghiduongthai',
        'dia_chi', 'chan_doan', 'pp_dieutri', 'mo_ta', 'ghi_chu', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
