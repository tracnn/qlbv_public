<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VpkdUngVien extends Model
{
    protected $table = 'xml3176_vpkd_ung_vien';

    protected $fillable = [
        'lan_hoc_id', 'loai', 'ma_vat_pham', 'ten_vat_pham', 'ten_khop',
        'so_dong', 'so_lien_ket', 'ty_le_gan', 'dvkt',
    ];

    protected $attributes = ['ten_khop' => ''];

    protected $casts = [
        'lan_hoc_id' => 'integer',
        'so_dong' => 'integer',
        'so_lien_ket' => 'integer',
        'ty_le_gan' => 'float',
        'dvkt' => 'array',
    ];
}
