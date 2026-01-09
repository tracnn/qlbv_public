<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml9 extends Model
{
    protected $fillable = [
        'ma_lk',
        'ma_bhxh_nnd',
        'ma_the_nnd',
        'ho_ten_nnd',
        'ngaysinh_nnd',
        'ma_dantoc_nnd',
        'so_cccd_nnd',
        'ngaycap_cccd_nnd',
        'noicap_cccd_nnd',
        'noi_cu_tru_nnd',
        'ma_quoctich',
        'matinh_cu_tru',
        'mahuyen_cu_tru',
        'maxa_cu_tru',
        'ho_ten_cha',
        'ma_the_tam',
        'ho_ten_con',
        'gioi_tinh_con',
        'so_con',
        'lan_sinh',
        'so_con_song',
        'can_nang_con',
        'ngay_sinh_con',
        'noi_sinh_con',
        'tinh_trang_con',
        'sinhcon_phauthuat',
        'sinhcon_duoi32tuan',
        'ghi_chu',
        'nguoi_do_de',
        'nguoi_ghi_phieu',
        'ngay_ct',
        'so',
        'quyen_so',
        'ma_ttdv',
        'du_phong',
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176XmlErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML9');
    }
}
