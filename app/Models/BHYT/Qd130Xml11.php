<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130Xml11 extends Model
{
    protected $fillable = [
        'ma_lk',
        'so_ct',
        'so_seri',
        'so_kcb',
        'don_vi',
        'ma_bhxh',
        'ma_the_bhyt',
        'chan_doan_rv',
        'pp_dieutri',
        'ma_dinh_chi_thai',
        'nguyennhan_dinhchi',
        'tuoi_thai',
        'so_ngay_nghi',
        'tu_ngay',
        'den_ngay',
        'ho_ten_cha',
        'ho_ten_me',
        'ma_ttdv',
        'ma_bs',
        'ngay_ct',
        'ma_the_tam',
        'mau_so',
    ];

    public function Qd130Xml1()
    {
        return $this->belongsTo('App\Models\BHYT\Qd130Xml1', 'ma_lk', 'ma_lk');
    }

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Qd130XmlErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML11');
    }
}
