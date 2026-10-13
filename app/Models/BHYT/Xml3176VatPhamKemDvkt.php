<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VatPhamKemDvkt extends Model
{
    const LOAI_VTYT = 'VTYT';
    const LOAI_THUOC = 'THUOC';
    const NGUON_HOC = 'HOC';
    const NGUON_TAY = 'TAY';
    /** Ghi chu do lenh hoc dat khi tu tat dong; chi dong mang dung ghi chu nay moi duoc tu bat lai. */
    const GHI_CHU_TU_TAT = 'Học lại: dưới ngưỡng';

    protected $table = 'xml3176_vat_pham_kem_dvkt';

    protected $fillable = [
        'loai', 'ma_vat_pham', 'ten_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt',
        'nguon', 'so_lan_gan', 'ty_le_gan', 'hieu_luc', 'ghi_chu',
        'nguoi_sua_id', 'sua_luc',
    ];

    protected $attributes = [
        'ten_khop' => '',
        'so_lan_gan' => 0,
        'hieu_luc' => true,
    ];

    protected $casts = [
        'so_lan_gan' => 'integer',
        'ty_le_gan' => 'float',
        'hieu_luc' => 'boolean',
        'nguoi_sua_id' => 'integer',
    ];

    protected $dates = ['sua_luc'];
}
