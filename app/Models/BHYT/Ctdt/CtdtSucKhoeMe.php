<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtSucKhoeMe extends Model
{
    protected $table = 'ctdt_suc_khoe_me';

    protected $fillable = [
        'chung_tu_id',
        'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
        'ma_khoa', 'ma_tinhcutru', 'ma_xacutru', 'nghe_nghiep',
        'ngay_vao', 'ngay_ra',
        'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri',
        'dia_chi', 'chan_doan', 'pp_dieutri', 'ket_luan',
        'tinhtrangbenhhientai', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
