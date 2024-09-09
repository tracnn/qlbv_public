<?php

namespace App\Models\CheckBHYT;

use Illuminate\Database\Eloquent\Model;

class check_hein_card extends Model
{
    // Thêm các trường vào fillable
    protected $fillable = [
        'ma_lk', 'ma_tracuu', 'ma_kiemtra', 'ma_ketqua', 'ghi_chu', 'ma_the', 'ho_ten', 'ngay_sinh',
        'dia_chi', 'ma_the_cu', 'ma_the_moi', 'ma_dkbd', 'cq_bhxh', 'gioi_tinh', 'gt_the_tu', 'gt_the_den',
        'ma_kv', 'ngay_du5nam', 'maso_bhxh', 'gt_the_tu_moi', 'gt_the_den_moi', 'ma_dkbd_moi', 'ten_dkbd_moi'
    ];

    public function xml1()
    {
        return $this->belongsTo('App\Models\BHYT\XML1', 'ma_lk', 'ma_lk');
    }

    public function his_treatment()
    {
        return $this->belongsTo('App\Models\HISPro\HIS_TREATMENT', 'ma_lk', 'treatment_code');
    }
}
