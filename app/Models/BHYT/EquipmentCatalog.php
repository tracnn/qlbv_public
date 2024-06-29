<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class EquipmentCatalog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ten_tb',
        'ky_hieu',
        'congty_sx',
        'nuoc_sx',
        'nam_sx',
        'nam_sd',
        'ma_may',
        'so_luu_hanh',
        'hd_tu',
        'hd_den',
        'tu_ngay',
        'den_ngay',
    ];
}
