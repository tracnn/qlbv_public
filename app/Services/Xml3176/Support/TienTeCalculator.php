<?php

namespace App\Services\Xml3176\Support;

/**
 * Cong thuc tien tung dong XML2/XML3 theo chuan du lieu dau ra, ban SUA DOI theo
 * QD 4750/QD-BYT (ban QD 130 goc da bi bai bo).
 *
 * Vi sao can: Xml3176CompleteChecker::checkExpenseErrors() da kiem TONG cap ho so,
 * nhung tong khop khong suy ra tung dong dung - hai dong sai nguoc chieu nhau van
 * cho tong dung.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class TienTeCalculator
{
    /** THANH_TIEN_BV = SO_LUONG * DON_GIA_BV * TYLE_TT_DV/100 */
    public static function thanhTienBvXml3($soLuong, $donGiaBv, $tyLeTtDv): float
    {
        return round((float) $soLuong * (float) $donGiaBv * (float) $tyLeTtDv / 100, 2);
    }

    /** THANH_TIEN_BH = SO_LUONG * DON_GIA_BH * TYLE_TT_DV/100 * TYLE_TT_BH/100 */
    public static function thanhTienBhXml3($soLuong, $donGiaBh, $tyLeTtDv, $tyLeTtBh): float
    {
        return round(
            (float) $soLuong * (float) $donGiaBh * (float) $tyLeTtDv / 100 * (float) $tyLeTtBh / 100,
            2
        );
    }

    /** XML2 khong co TYLE_TT_DV: THANH_TIEN_BV = SO_LUONG * DON_GIA */
    public static function thanhTienBvXml2($soLuong, $donGia): float
    {
        return round((float) $soLuong * (float) $donGia, 2);
    }

    /** THANH_TIEN_BH = SO_LUONG * DON_GIA * TYLE_TT_BH/100 */
    public static function thanhTienBhXml2($soLuong, $donGia, $tyLeTtBh): float
    {
        return round((float) $soLuong * (float) $donGia * (float) $tyLeTtBh / 100, 2);
    }

    /** T_BHTT = THANH_TIEN_BH * MUC_HUONG/100 (khi khong co T_NGUONKHAC). */
    public static function tBhtt($thanhTienBh, $mucHuong): float
    {
        return round((float) $thanhTienBh * (float) $mucHuong / 100, 2);
    }

    /** T_NGUONKHAC = T_NGUONKHAC_NSNN + _VTNN + _VTTN + _CL */
    public static function tongNguonKhac($nsnn, $vtnn, $vttn, $cl): float
    {
        return round((float) $nsnn + (float) $vtnn + (float) $vttn + (float) $cl, 2);
    }

    /**
     * So sanh tien co bien do. KHONG dung '!=' truc tiep tren so thuc: ma cu trong
     * checkExpenseErrors lam vay va de bao oan vi loi dau phay dong.
     */
    public static function lech($thucTe, $kyVong, float $saiSo): bool
    {
        return abs((float) $thucTe - (float) $kyVong) > $saiSo;
    }

    /** Toan hang co mat va la so? Thieu can cu thi quy tac phai im lang. */
    public static function laSo($gt): bool
    {
        return $gt !== null && $gt !== '' && is_numeric($gt);
    }

    /** Ty le hop le nam trong khoang (0, 100]. */
    public static function tyLeHopLe($gt): bool
    {
        if (!self::laSo($gt)) {
            return false;
        }

        $v = (float) $gt;

        return $v > 0 && $v <= 100;
    }
}
