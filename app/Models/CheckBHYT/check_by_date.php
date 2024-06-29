<?php

namespace App\Models\CheckBHYT;

use Illuminate\Database\Eloquent\Model;

class check_by_date extends Model
{
    protected $fillable = [
        'NGAY_DL',
        'MA_LOI',
        'LOAI_LOI',
        'SO_LUONG',
        'MO_TA'
    ];
}
