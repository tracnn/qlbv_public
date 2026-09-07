<?php

namespace App\Models\Mcct;

use Illuminate\Database\Eloquent\Model;

class McctTraCuu extends Model
{
    protected $table = 'mcct_tra_cuu';

    protected $fillable = [
        'ma_cskcb', 'ma_the', 'ho_ten', 'ngay_sinh',
        'ma_ket_qua', 'ghi_chu',
        'the_ho_ten', 'the_ngay_sinh', 'the_ngay_ket_thuc', 'the_ma_bhxh',
        'luy_ke_lon_nhat', 'nguong_ap_dung', 'du_dieu_kien_mien',
        'so_tien_con_phai_dong', 'da_dong_truoc_moc',
        'nguon', 'tra_boi', 'tra_luc',
    ];

    protected $dates = ['tra_luc'];

    public function chiPhi()
    {
        return $this->hasMany(McctChiPhi::class, 'tra_cuu_id');
    }
}
