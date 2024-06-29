<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XML2 extends Model
{
    protected $table = 'xml2s';

    // Thêm các thuộc tính vào fillable
    protected $fillable = [
        'ma_lk', 'stt', 'ma_thuoc', 'ma_nhom', 'ten_thuoc', 'don_vi_tinh', 'ham_luong', 'duong_dung', 'lieu_dung',
        'so_dang_ky', 'tt_thau', 'pham_vi', 'so_luong', 'don_gia', 'tyle_tt', 'thanh_tien', 'muc_huong', 't_nguon_khac',
        't_bntt', 't_bhtt', 't_bncct', 't_ngoaids', 'ma_khoa', 'ma_bac_si', 'ma_benh', 'ngay_yl', 'ma_pttt'
    ];
    
    public function cat_cond_pharma(){
        return $this->hasOne('App\Models\BHYT\cat_cond_pharma','pharma_code','ma_thuoc');
    }

    public function xml1()
    {
        return $this->belongsTo('App\Models\BHYT\XML1', 'ma_lk', 'ma_lk');
    }
}