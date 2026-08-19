<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtGiayBaoTu extends Model
{
    protected $table = 'ctdt_giay_bao_tu';

    protected $fillable = [
        'chung_tu_id',
        'ma_gbt', 'ma_bn', 'ma_hsba', 'ho_ten', 'ngay_sinh', 'gioi_tinh',
        'ma_the', 'ma_dantoc', 'ma_quoctich',
        'matinh_thuongtru', 'mahuyen_thuongtru', 'maxa_thuongtru',
        'matinh_hientai', 'mahuyen_hientai', 'maxa_hientai',
        'loai_giayto', 'so_giayto', 'ngay_cap', 'noi_cap',
        'ngaygio_vv', 'ngay_tv', 'tinh_trang_tv',
        'nguoi_ghigiay', 'nguoi_thanthich', 'ttruong_dvi',
        'so_baotu', 'quyen_so', 'ngay_capgiaybt', 'so_baotu_bd', 'quyen_so_bd',
        'macskcb', 'ma_bhxh', 'benh_icd10_id',
        'dchi_thuongtru', 'dchi_hientai', 'nguyennhan_tv',
        'diachi_cskcb', 'benh_icd10_ten',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
