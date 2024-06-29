<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XML3 extends Model
{
    protected $table = 'xml3s';

    protected $fillable = [
        'ma_lk', 'stt', 'ma_dich_vu', 'ma_vat_tu', 'ma_nhom', 'goi_vtyt', 'ten_vat_tu',
        'ten_dich_vu', 'don_vi_tinh', 'pham_vi', 'so_luong', 'don_gia', 'tt_thau',
        'tyle_tt', 'thanh_tien', 't_trantt', 'muc_huong', 't_nguonkhac', 't_bntt',
        't_bhtt', 't_bncct', 't_ngoaids', 'ma_khoa', 'ma_giuong', 'ma_bac_si', 'ma_benh',
        'ngay_yl', 'ngay_kq', 'ma_pttt'
    ];
    
    public function cat_cond_service(){
        return $this->hasOne('App\Models\BHYT\cat_cond_service', 'service_code', 'ma_dich_vu');
    }

    public function xml1()
    {
        return $this->belongsTo('App\Models\BHYT\XML1', 'ma_lk', 'ma_lk');
    }
}