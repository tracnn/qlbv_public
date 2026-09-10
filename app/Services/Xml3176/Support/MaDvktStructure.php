<?php

namespace App\Services\Xml3176\Support;

/**
 * Doc cau truc ma dich vu XML3 theo quy dinh tai truong MA_PTTT_QT cua chuan du lieu
 * dau ra (QD 130/QD-BYT, sua doi theo QD 4750/QD-BYT).
 *
 * Khac MaDvktMatcher: lop do CHUAN HOA ma (bo hau to de tra danh muc), lop nay tra ve
 * THANH PHAN cua ma. Gop lai se lam mot lop mang hai trach nhiem.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class MaDvktStructure
{
    /** Phan than: bo hau to sau dau '_' cuoi cung. */
    private static function than($ma): string
    {
        $ma = trim((string) $ma);
        $pos = strrpos($ma, '_');

        return $pos === false ? $ma : trim(substr($ma, 0, $pos));
    }

    public static function hauTo($ma): string
    {
        $ma = trim((string) $ma);
        $pos = strrpos($ma, '_');

        return $pos === false ? '' : trim(substr($ma, $pos + 1));
    }

    /**
     * DVKT da chi dinh nhung khong the tiep tuc thuc hien (khoan 3 Dieu 7 TT
     * 39/2018/TT-BYT): ma co hau to '_TB', khi do DON_GIA_BH = 0 va DON_GIA_BV = 0.
     */
    public static function laKhongThucHien($ma): bool
    {
        return self::hauTo($ma) === 'TB';
    }

    /**
     * DVKT da duoc phe duyet thuc hien nhung chua co muc gia: 04 ky tu cuoi cua phan
     * than la '0000', khi do DON_GIA_BH = 0.
     */
    public static function laChuaCoGia($ma): bool
    {
        $than = self::than($ma);

        return $than !== '' && substr($than, -4) === '0000';
    }

    /** Van chuyen nguoi benh: VC.XXXXX. */
    public static function laVanChuyen($ma): bool
    {
        return strpos(trim((string) $ma), 'VC.') === 0;
    }

    public static function maCoSoVanChuyen($ma): string
    {
        if (!self::laVanChuyen($ma)) {
            return '';
        }

        return trim(substr(trim((string) $ma), 3));
    }

    /**
     * Chuyen mau benh pham hoac chuyen nguoi benh den co so khac de thuc hien dich vu
     * can lam sang (TT 09/2019/TT-BYT): XX.YYYY.ZZZZ.K.WWWWW.
     */
    public static function maCoSoChuyenMau($ma): string
    {
        $ma = trim((string) $ma);

        if (!preg_match('/\.K\.([^.]+)$/', $ma, $khop)) {
            return '';
        }

        return trim($khop[1]);
    }
}
