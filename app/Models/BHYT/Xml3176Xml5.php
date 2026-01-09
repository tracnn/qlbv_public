<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml5 extends Model
{
    protected $table = 'xml3176_xml5s';

    protected $fillable = [
        'ma_lk',
        'stt',
        'dien_bien_ls',
        'giai_doan_benh',
        'hoi_chan',
        'phau_thuat',
        'thoi_diem_dbls',
        'nguoi_thuc_hien',
        'du_phong'
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML5')
                    ->whereColumn('stt', 'stt');
    }

    public function Xml3176Xml1()
    {
        return $this->belongsTo('App\Models\BHYT\Xml3176Xml1', 'ma_lk', 'ma_lk');
    }
}
