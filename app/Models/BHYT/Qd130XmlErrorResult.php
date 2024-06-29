<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130XmlErrorResult extends Model
{
    protected $fillable = [
        'xml', 
        'ma_lk', 
        'stt', 
        'ngay_yl', 
        'ngay_kq', 
        'error_code', 
        'description'
    ];

    public function Qd130XmlErrorCatalog()
    {
        return $this->hasOne('App\Models\BHYT\Qd130XmlErrorCatalog', 'error_code', 'error_code');
    }
}
