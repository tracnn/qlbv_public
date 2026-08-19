<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtCt06 extends Model
{
    protected $table = 'ctdt_ct06';

    protected $fillable = [
        'chung_tu_id',
        'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra',
        'nguoi_dai_dien', 'ma_bs', 'ten_bs', 'ten_dvi', 'so_kcb',
        'ngay_ct', 'so_seri', 'ma_ct',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd',
        'matinh_cu_tru', 'maxa_cu_tru', 'tuoi_thai', 'benh_icd10_id',
        'chan_doan', 'noi_cu_tru_nnd', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
