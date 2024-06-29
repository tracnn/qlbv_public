<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicineCatalog extends Model
{
    protected $fillable = [
        'ma_thuoc',
        'ten_hoat_chat',
        'ten_thuoc',
        'don_vi_tinh',
        'ham_luong',
        'duong_dung',
        'ma_duong_dung',
        'dang_bao_che',
        'so_dang_ky',
        'so_luong',
        'don_gia',
        'don_gia_bh',
        'quy_cach',
        'nha_sx',
        'nuoc_sx',
        'nha_thau',
        'tt_thau',
        'tu_ngay',
        'den_ngay',
        'ma_cskcb',
        'loai_thuoc',
        'loai_thau',
        'ht_thau',
    ];
}
