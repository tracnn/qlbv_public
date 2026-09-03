<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * Danh sach khoa cho dropdown loc XML3176.
     *
     * Gom nhom theo ma_khoa (bang co the co nhieu dong cung ma_khoa, khac ma_loai_kcb
     * hoac ky hieu luc) va bo cac dong ma_khoa rong/null. Tra ve {ma_khoa, ten_khoa}
     * sap xep tang dan theo ma_khoa. MAX(ten_khoa) de hop le voi ONLY_FULL_GROUP_BY.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function danhSachChonKhoa()
    {
        return static::query()
            ->select('ma_khoa', DB::raw('MAX(ten_khoa) as ten_khoa'))
            ->whereNotNull('ma_khoa')
            ->where('ma_khoa', '<>', '')
            ->groupBy('ma_khoa')
            ->orderBy('ma_khoa')
            ->get();
    }
}
