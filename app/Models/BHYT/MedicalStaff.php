<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicalStaff extends Model
{
    protected $fillable = [
        'ma_loai_kcb',
        'ma_khoa',
        'ten_khoa',
        'ma_bhxh',
        'ho_ten',
        'gioi_tinh',
        'chucdanh_nn',
        'vi_tri',
        'macchn',
        'ngaycap_cchn',
        'noicap_cchn',
        'phamvi_cm',
        'phamvi_cmbs',
        'dvkt_khac',
        'vb_phancong',
        'thoigian_dk',
        'thoigian_ngay',
        'thoigian_tuan',
        'cskcb_khac',
        'cskcb_cgkt',
        'qd_cgkt',
        'tu_ngay',
        'den_ngay'
    ];
}
