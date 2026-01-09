<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml8 extends Model
{
    protected $table = 'xml3176_xml8s';

    protected $fillable = [
        'ma_lk',
        'ma_loai_kcb',
        'ho_ten_cha',
        'ho_ten_me',
        'nguoi_giam_ho',
        'don_vi',
        'ngay_vao',
        'ngay_ra',
        'chan_doan_vao',
        'chan_doan_rv',
        'qt_benhly',
        'tomtat_kq',
        'pp_dieutri',
        'ngay_sinhcon',
        'ngay_conchet',
        'so_conchet',
        'ket_qua_dtri',
        'ghi_chu',
        'ma_ttdv',
        'ngay_ct',
        'ma_the_tam',
        'du_phong',
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML8');
    }
}
