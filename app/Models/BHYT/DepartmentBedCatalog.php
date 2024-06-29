<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class DepartmentBedCatalog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ma_loai_kcb',
        'ma_khoa',
        'ten_khoa',
        'ban_kham',
        'giuong_pd',
        'giuong_2015',
        'giuong_tk',
        'giuong_hstc',
        'giuong_hscc',
        'ldlk',
        'lien_khoa',
        'den_ngay'
    ];
}
