<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplyCatalog extends Model
{
    protected $fillable = [
        'ma_vat_tu',
        'nhom_vat_tu',
        'ten_vat_tu',
        'ma_hieu',
        'quy_cach',
        'hang_sx',
        'nuoc_sx',
        'don_vi_tinh',
        'don_gia',
        'don_gia_bh',
        'tyle_tt_bh',
        'so_luong',
        'dinh_muc',
        'nha_thau',
        'tt_thau',
        'tu_ngay',
        'den_ngay_hd',
        'ma_cskcb',
        'loai_thau',
        'ht_thau',
        'den_ngay',
    ];
}
