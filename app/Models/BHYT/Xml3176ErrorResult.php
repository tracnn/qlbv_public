<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176ErrorResult extends Model
{
    protected $table = 'xml3176_error_results';

    protected $fillable = [
        'xml', 
        'ma_lk', 
        'stt', 
        'ngay_yl', 
        'ngay_kq', 
        'error_code', 
        'description',
        'critical_error',
    ];

    public function Xml3176ErrorCatalog()
    {
        return $this->hasOne('App\Models\BHYT\Xml3176ErrorCatalog', 'error_code', 'error_code');
    }
}
