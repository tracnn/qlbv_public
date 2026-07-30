<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class DepartmentBedCatalog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ma_loai_kcb',
        'ma_khoa',
        'ten_khoa',
        'ban_kham',
        'giuong_pd',
        'giuong_2015',
        'giuong_tk',
        'giuong_hstc',
        'giuong_hscc',
        'ldlk',
        'lien_khoa',
        'tu_ngay',
        'den_ngay',
        'ma_cskcb',
    ];

    /**
     * Loc dong danh muc theo co so kham chua benh.
     *
     * Dong co ma_cskcb RONG (null hoac chuoi rong) dung chung cho MOI co so. Nho vay du lieu
     * danh muc cu - von chua gan ma co so - van tiep tuc chay, khong gay thoai lui khi trien
     * khai.
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
