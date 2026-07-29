<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    protected $fillable = [
        'ma_dich_vu',
        'ten_dich_vu',
        'ma_cskcb',
        'don_gia',
        'quy_trinh',
        'cskcb_cgkt',
        'cskcb_cls',
        'tu_ngay',
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
