<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XML4 extends Model
{
    protected $table = 'xml4s';
    
    protected $fillable = [
        'ma_lk', 'stt', 'ma_dich_vu', 'ma_chi_so', 'ten_chi_so', 'gia_tri',
        'ma_may', 'mo_ta', 'ket_luan', 'ngay_kq'
    ];
}