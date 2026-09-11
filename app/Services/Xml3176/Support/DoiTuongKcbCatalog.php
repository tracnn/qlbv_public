<?php

namespace App\Services\Xml3176\Support;

/**
 * Tra danh muc ma doi tuong den kham benh, chua benh (Phu luc 1 do Bo Y te ban hanh).
 *
 * Danh muc do NGUOI GOI doc tu config('doi_tuong_kcb') roi truyen vao, de lop nay giu
 * tinh thuan - cung loi TienTeCalculator da dung.
 *
 * KHONG khop tien to. Ma doi tuong dung dang phan cap co dau cham ('1.17', '3.1'), nen
 * khop tien to lam '3' nuot ca 3.1, 3.2, 3.3, 3.6 - dung khiem khuyet dang ton tai
 * trong config xml1.ma_doituong_kcb_trai_tuyen ma dot nay di va.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class DoiTuongKcbCatalog
{
    /** Chi trim. KHONG cat hau to, KHONG khop tien to. */
    public static function chuanHoa($ma): string
    {
        return trim((string) $ma);
    }

    public static function coTrongDanhMuc($ma, array $danhMuc): bool
    {
        $ma = self::chuanHoa($ma);

        return $ma !== '' && array_key_exists($ma, $danhMuc);
    }

    /**
     * Doc mot thuoc tinh cua muc. Ma khong co trong danh muc, hoac muc khong khai thuoc
     * tinh do, deu tra $macDinh - khoa vang nghia la thuoc tinh do khong ap dung.
     */
    public static function thuocTinh($ma, array $danhMuc, string $khoa, $macDinh = null)
    {
        $ma = self::chuanHoa($ma);

        if (!array_key_exists($ma, $danhMuc) || !is_array($danhMuc[$ma])) {
            return $macDinh;
        }

        return array_key_exists($khoa, $danhMuc[$ma]) ? $danhMuc[$ma][$khoa] : $macDinh;
    }

    public static function laTuDen($ma, array $danhMuc): bool
    {
        return (bool) self::thuocTinh($ma, $danhMuc, 'tu_den', false);
    }
}
