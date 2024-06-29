<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XML5 extends Model
{
    protected $table = 'xml5s';
    
    protected $fillable = [
        'ma_lk', 'stt', 'dien_bien', 'hoi_chan', 'phau_thuat', 'ngay_yl'
    ];
}