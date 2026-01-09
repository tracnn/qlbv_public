<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml4 extends Model
{
    protected $table = 'xml3176_xml4s';

    protected $fillable = [
        'ma_lk',
        'stt',
        'ma_dich_vu',
        'ma_chi_so',
        'ten_chi_so',
        'gia_tri',
        'don_vi_do',
        'mo_ta',
        'ket_luan',
        'ngay_kq',
        'ma_bs_doc_kq',
        'du_phong'
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML4')
                    ->whereColumn('stt', 'stt');
    }
}
