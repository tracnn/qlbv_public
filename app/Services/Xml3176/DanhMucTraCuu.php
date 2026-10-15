<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\ServiceCatalog;

/**
 * Tra danh muc DVKT / thuoc / VTYT qua bo dem tinh (Xml3176BoDem).
 *
 * Moi ham giu NGUYEN dieu kien truy van cu, luon loc theo co so (cuaCoSo), va khoa bo dem
 * gom MOI gia tri truy van dung. Gia tri nho (model/Collection) chi duoc DOC, khong sua.
 * ma_cskcb duoc cuaCoSo cat khoang trang nen khoa dung gia tri da cat (tuong duong).
 */
class DanhMucTraCuu
{
    /** DVKT co ton tai (trong pham vi co so) khong. */
    public static function dvktTonTai($maCskcb, $maDichVu): bool
    {
        return (bool) Xml3176BoDem::nho(
            'danh_muc.dvkt_ton_tai',
            serialize([trim((string) $maCskcb), $maDichVu]),
            function () use ($maCskcb, $maDichVu) {
                return ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $maDichVu)->exists();
            }
        );
    }

    /** Cac dong DVKT con hieu luc tai $ngayVao (Ymd). */
    public static function dvktHieuLuc($maCskcb, $maDichVu, $ngayVao)
    {
        return Xml3176BoDem::nho(
            'danh_muc.dvkt_hieu_luc',
            serialize([trim((string) $maCskcb), $maDichVu, $ngayVao]),
            function () use ($maCskcb, $maDichVu, $ngayVao) {
                return ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $maDichVu)
                    ->where('tu_ngay', '<=', $ngayVao)
                    ->where(function ($query) use ($ngayVao) {
                        $query->where('den_ngay', '>=', $ngayVao)
                              ->orWhereNull('den_ngay');
                    })->get();
            }
        );
    }

    /** Dong VTYT theo ma vat tu (Collection). */
    public static function vtyt($maCskcb, $maVatTu)
    {
        return Xml3176BoDem::nho(
            'danh_muc.vtyt',
            serialize([trim((string) $maCskcb), $maVatTu]),
            function () use ($maCskcb, $maVatTu) {
                return MedicalSupplyCatalog::cuaCoSo($maCskcb)->where('ma_vat_tu', $maVatTu)->get();
            }
        );
    }

    /** Dong thuoc khop ma + ham luong + SDK + tt thau (nhanh chinh), hoac null. */
    public static function thuocKhop($maCskcb, $maThuoc, $hamLuong, $soDangKy, $ttThau)
    {
        return Xml3176BoDem::nho(
            'danh_muc.thuoc_khop',
            serialize([trim((string) $maCskcb), $maThuoc, $hamLuong, $soDangKy, $ttThau]),
            function () use ($maCskcb, $maThuoc, $hamLuong, $soDangKy, $ttThau) {
                return MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $maThuoc)
                    ->where('ham_luong', $hamLuong)
                    ->where('so_dang_ky', $soDangKy)
                    ->where('tt_thau', 'LIKE', $ttThau . '%')
                    ->first();
            }
        );
    }

    /** Cac ham luong duoc duyet cung ma + SDK + tt thau (Collection da unique). */
    public static function thuocHamLuongDuyet($maCskcb, $maThuoc, $soDangKy, $ttThau)
    {
        return Xml3176BoDem::nho(
            'danh_muc.thuoc_ham_luong_duyet',
            serialize([trim((string) $maCskcb), $maThuoc, $soDangKy, $ttThau]),
            function () use ($maCskcb, $maThuoc, $soDangKy, $ttThau) {
                return MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $maThuoc)
                    ->where('so_dang_ky', $soDangKy)
                    ->where('tt_thau', 'LIKE', $ttThau . '%')
                    ->pluck('ham_luong')->unique()->values();
            }
        );
    }

    /** Thuoc co ma nay trong danh muc khong; $hamLuong / $soDangKy = null nghia la khong loc them. */
    public static function thuocTonTai($maCskcb, $maThuoc, $hamLuong = null, $soDangKy = null): bool
    {
        // Phan biet "khong loc" voi "loc theo gia tri null" bang so tham so duoc truyen.
        $soLoc = func_num_args() - 2;

        return (bool) Xml3176BoDem::nho(
            'danh_muc.thuoc_ton_tai',
            serialize([trim((string) $maCskcb), $maThuoc, $soLoc, $hamLuong, $soDangKy]),
            function () use ($maCskcb, $maThuoc, $hamLuong, $soDangKy, $soLoc) {
                $q = MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $maThuoc);
                if ($soLoc >= 1) {
                    $q->where('ham_luong', $hamLuong);
                }
                if ($soLoc >= 2) {
                    $q->where('so_dang_ky', $soDangKy);
                }

                return $q->exists();
            }
        );
    }
}
