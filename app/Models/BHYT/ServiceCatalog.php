<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    protected $fillable = [
        'ma_dich_vu',
        'ten_dich_vu',
        'don_gia',
        'quy_trinh',
        'cskcb_cgkt',
        'cskcb_cls',
        'tu_ngay',
        'den_ngay',
    ];
}
