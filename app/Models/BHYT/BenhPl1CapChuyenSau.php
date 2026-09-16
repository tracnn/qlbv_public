<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class BenhPl1CapChuyenSau extends Model
{
    protected $table = 'benh_pl1_cap_chuyen_sau';
    protected $fillable = ['stt', 'ten_benh', 'ma_icd', 'loai', 'tuoi_duoi', 'dieu_kien', 'is_active'];
}
