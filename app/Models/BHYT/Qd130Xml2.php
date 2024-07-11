<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130Xml2 extends Model
{
    protected $fillable = [
        'ma_lk',
        'stt',
        'ma_thuoc',
        'ma_pp_chebien',
        'ma_cskcb_thuoc',
        'ma_nhom',
        'ten_thuoc',
        'don_vi_tinh',
        'ham_luong',
        'duong_dung',
        'dang_bao_che',
        'lieu_dung',
        'cach_dung',
        'so_dang_ky',
        'tt_thau',
        'pham_vi',
        'tyle_tt_bh',
        'so_luong',
        'don_gia',
        'thanh_tien_bv',
        'thanh_tien_bh',
        't_nguonkhac_nsnn',
        't_nguonkhac_vtnn',
        't_nguonkhac_vttn',
        't_nguonkhac_cl',
        't_nguonkhac',
        'muc_huong',
        't_bhtt',
        't_bncct',
        't_bntt',
        'ma_khoa',
        'ma_bac_si',
        'ma_dich_vu',
        'ngay_yl',
        'ngay_th_yl',
        'ma_pttt',
        'nguon_ctra',
        'vet_thuong_tp',
        'du_phong'
    ];

    public function Qd130Xml1()
    {
        return $this->belongsTo('App\Models\BHYT\Qd130Xml1', 'ma_lk', 'ma_lk');
    }

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Qd130XmlErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML2')
                    ->whereColumn('stt', 'stt');
    }
}
