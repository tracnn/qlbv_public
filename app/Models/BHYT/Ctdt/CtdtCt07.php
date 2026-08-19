<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtCt07 extends Model
{
    protected $table = 'ctdt_ct07';

    protected $fillable = [
        'chung_tu_id',
        'ma_ct', 'mau_so', 'so_seri', 'so_kcb', 'ma_bhxh', 'ma_the',
        'ho_ten', 'ngay_sinh', 'gioi_tinh',
        'tu_ngay', 'den_ngay', 'ho_ten_cha', 'ho_ten_me',
        'thu_truong_dv', 'ma_cchn', 'ten_nguoi_hanh_nghe',
        'ngay_chung_tu', 'tekt',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'ngay_kcb', 'benh_icd10_id',
        'don_vi', 'chandoan_dieutri', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
