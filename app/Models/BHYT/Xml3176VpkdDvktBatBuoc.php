<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

/** DVKT bat buoc kem vat pham (R3). Hang so nguon/ghi chu dung chung voi Xml3176VatPhamKemDvkt. */
class Xml3176VpkdDvktBatBuoc extends Model
{
    protected $table = 'xml3176_vpkd_dvkt_bat_buoc';

    protected $fillable = [
        'ma_dvkt', 'ten_dvkt', 'so_ho_so', 'so_ho_so_co_kem', 'ty_le', 'nguon', 'hieu_luc', 'ghi_chu',
        'nguoi_sua_id', 'sua_luc',
    ];

    protected $attributes = ['hieu_luc' => true, 'so_ho_so' => 0, 'so_ho_so_co_kem' => 0];

    protected $casts = [
        'so_ho_so' => 'integer',
        'so_ho_so_co_kem' => 'integer',
        'ty_le' => 'float',
        'hieu_luc' => 'boolean',
        'nguoi_sua_id' => 'integer',
    ];

    protected $dates = ['sua_luc'];
}
