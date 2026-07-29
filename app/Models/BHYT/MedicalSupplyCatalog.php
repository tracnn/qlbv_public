<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplyCatalog extends Model
{
    protected $fillable = [
        'ma_vat_tu',
        'nhom_vat_tu',
        'ten_vat_tu',
        'ma_hieu',
        'quy_cach',
        'hang_sx',
        'nuoc_sx',
        'don_vi_tinh',
        'don_gia',
        'don_gia_bh',
        'tyle_tt_bh',
        'so_luong',
        'dinh_muc',
        'nha_thau',
        'tt_thau',
        'tu_ngay',
        'den_ngay_hd',
        'ma_cskcb',
        'loai_thau',
        'ht_thau',
        'den_ngay',
    ];

    /**
     * Loc dong danh muc theo co so kham chua benh.
     *
     * Dong co ma_cskcb RONG (null hoac chuoi rong) dung chung cho MOI co so. Nho vay du
     * lieu danh muc cu - von chua gan ma co so - van tiep tuc chay, khong gay thoai lui
     * khi trien khai.
     *
     * @param string|null $maCskcb null hoac rong = khong loc
     */
    public function scopeCuaCoSo($q, $maCskcb)
    {
        $maCskcb = trim((string) $maCskcb);

        if ($maCskcb === '') {
            return $q;
        }

        return $q->where(function ($w) use ($maCskcb) {
            $w->whereNull('ma_cskcb')
              ->orWhere('ma_cskcb', '')
              ->orWhere('ma_cskcb', $maCskcb);
        });
    }
}
