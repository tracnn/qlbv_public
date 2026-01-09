<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml10 extends Model
{
    protected $table = 'xml3176_xml10s';

    protected $fillable = [
        'ma_lk',
        'so_seri',
        'so_ct',
        'so_ngay',
        'don_vi',
        'chan_doan_rv',
        'tu_ngay',
        'den_ngay',
        'ma_ttdv',
        'ten_bs',
        'ma_bs',
        'ngay_ct',
        'du_phong',
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML10');
    }
}
