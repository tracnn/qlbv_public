<?php

namespace App\Services\Xml3176\Support;

/**
 * Khoa khop vat pham (VTYT/thuoc) voi bang tra xml3176_vat_pham_kem_dvkt.
 *
 * VTYT mang ma nhom chung (<= 3 doan, vd N04.04.010) gop nhieu vat pham khac han nhau:
 * do tren CSDL that, N04.04.010 vua la "Ong dan luu duoi lon" vua la "Catheter chan doan
 * 4 cuc". Voi ma nhom chung, khoa phai gom ca ten chuan hoa. Ma day du va ma thuoc thi
 * khoa theo ma.
 */
class VatPhamKemDvktKey
{
    const DO_DAI_TEN_KHOP = 191;

    public static function laMaNhomChung($ma): bool
    {
        $ma = trim((string) $ma);

        return $ma !== '' && count(explode('.', $ma)) <= 3;
    }

    public static function tenKhop(string $loai, $ma, $ten): string
    {
        if ($loai !== 'VTYT' || !self::laMaNhomChung($ma)) {
            return '';
        }

        return mb_substr(TextNormalizer::chuan($ten), 0, self::DO_DAI_TEN_KHOP, 'UTF-8');
    }

    public static function khoa(string $loai, $ma, $ten): string
    {
        return trim((string) $ma) . '|' . self::tenKhop($loai, $ma, $ten);
    }

    /** Bo hau to _GT (DVKT gay te) de 12.0448.1187_GT khop 12.0448.1187. */
    public static function chuanHoaMaDvkt($ma): string
    {
        return preg_replace('/_GT$/i', '', trim((string) $ma));
    }
}
