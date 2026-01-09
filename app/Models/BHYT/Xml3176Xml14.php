<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml14 extends Model
{
    protected $table = 'xml3176_xml14s';

    protected $fillable = [
        'ma_lk',
        'so_giayhen_kl',
        'ma_cskcb',
        'ho_ten',
        'ngay_sinh',
        'gioi_tinh',
        'dia_chi',
        'ma_the_bhyt',
        'gt_the_den',
        'ngay_vao',
        'ngay_vao_noi_tru',
        'ngay_ra',
        'ngay_hen_kl',
        'chan_doan_rv',
        'ma_benh_chinh',
        'ma_benh_kt',
        'ma_benh_yhct',
        'ma_doituong_kcb',
        'ma_bac_si',
        'ma_ttdv',
        'ngay_ct',
        'du_phong',
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML14');
    }
}
