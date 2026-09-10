<?php

namespace App\Services\Xml3176\Support;

/**
 * Doc cau truc ma dich vu XML3 theo quy dinh tai truong MA_PTTT_QT cua chuan du lieu
 * dau ra (QD 130/QD-BYT, sua doi theo QD 4750/QD-BYT).
 *
 * Khac MaDvktMatcher: lop do CHUAN HOA ma (bo hau to de tra danh muc), lop nay tra ve
 * THANH PHAN cua ma. Gop lai se lam mot lop mang hai trach nhiem.
 *
 * LUU Y: MaDvktMatcher::maGoc() cat tai dau '_' DAU TIEN (strpos), con lop nay cat tai
 * dau '_' CUOI CUNG (strrpos). Tren ma co tu hai dau '_' tro len (vd: 02.0261.0319_A_B),
 * hai lop se cho ket qua KHAC NHAU. Du lieu that hien khong co ma nao nhu vay (0/1235 dong),
 * nen su khac biet nay chua bao gio lo ra. Ai dung ca hai lop tren cung mot ma phai biet
 * dieu nay.
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

    /**
     * Bo hau to (vd '_TB') TRUOC khi tach ma co so: 'VC.01234_TB' la dich vu van chuyen
     * da chi dinh nhung khong thuc hien duoc - khong bo hau to thi tra danh muc voi
     * '01234_TB' va bao oan MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND tren ho so dung.
     */
    public static function maCoSoVanChuyen($ma): string
    {
        if (!self::laVanChuyen($ma)) {
            return '';
        }

        return trim(substr(self::than($ma), 3));
    }

    /**
     * Chuyen mau benh pham hoac chuyen nguoi benh den co so khac de thuc hien dich vu
     * can lam sang (TT 09/2019/TT-BYT): XX.YYYY.ZZZZ.K.WWWWW.
     *
     * Bo hau to truoc khi tach, cung ly do nhu maCoSoVanChuyen().
     */
    public static function maCoSoChuyenMau($ma): string
    {
        $than = self::than($ma);

        if (!preg_match('/\.K\.([^.]+)$/', $than, $khop)) {
            return '';
        }

        return trim($khop[1]);
    }
}
