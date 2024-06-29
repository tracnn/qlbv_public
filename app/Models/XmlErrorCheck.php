<?php

namespace App\Models;

use App\Models\BHYT\XmlErrorCatalog;

use Illuminate\Database\Eloquent\Model;

class XmlErrorCheck extends Model
{
    protected $fillable = ['xml', 'ma_lk', 'stt', 'ngay_yl', 'ngay_kq', 'error_code', 'description'];

    public function xml1()
    {
        return $this->belongsTo('App\Models\BHYT\XML1', 'ma_lk', 'ma_lk');
    }

    public function xmlErrorCatalog()
    {
        return $this->hasOne('App\Models\BHYT\XmlErrorCatalog', 'error_code', 'error_code');
    }

}
