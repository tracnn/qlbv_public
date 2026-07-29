<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicineCatalog extends Model
{
    protected $fillable = [
        'ma_thuoc',
        'ten_hoat_chat',
        'ten_thuoc',
        'don_vi_tinh',
        'ham_luong',
        'duong_dung',
        'ma_duong_dung',
        'dang_bao_che',
        'so_dang_ky',
        'so_luong',
        'don_gia',
        'don_gia_bh',
        'quy_cach',
        'nha_sx',
        'nuoc_sx',
        'nha_thau',
        'tt_thau',
        'tu_ngay',
        'den_ngay',
        'ma_cskcb',
        'loai_thuoc',
        'loai_thau',
        'ht_thau',
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
