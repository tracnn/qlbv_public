<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtChungTu extends Model
{
    protected $table = 'ctdt_chung_tu';

    protected $fillable = [
        'ho_so_id', 'loai_ho_so', 'ma_chung_tu',
        'ma_the', 'so_cccd', 'ma_bhxh', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra',
        'noi_dung_goc',
    ];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id', 'id');
    }

    public function loi()
    {
        return $this->hasMany(CtdtLoi::class, 'chung_tu_id', 'id');
    }
}
