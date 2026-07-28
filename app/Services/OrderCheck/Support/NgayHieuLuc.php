<?php

namespace App\Services\OrderCheck\Support;

/**
 * Phan tich va so ngay hieu luc cua danh muc BHXH.
 *
 * Cot tu_ngay / den_ngay cua ba bang danh muc la varchar(255) ghi THO tu o Excel.
 * CatalogImportService khong chuan hoa gi - trong ca lop do chi ngaycap_cchn duoc doi qua
 * Date::excelToDateTimeObject(). Nen gia tri thuc te co the la serial Excel (45292), chuoi
 * Ymd, d/m/Y, Y-m-d hoac d-m-Y. Lop nay chap nhan ca nam dang.
 *
 * FAIL-SAFE: khong doc duoc ngay thi coi nhu CON hieu luc. Loi chat luong du lieu danh muc
 * khong duoc bien thanh mot tran lu vi pham gia - cung ly do voi CatalogLookup::sanSang().
 */
class NgayHieuLuc
{
    /** Serial Excel toi da chap nhan; 80000 tuong ung nam 2118 */
    const SERIAL_TOI_DA = 80000;

    /** Moc goc cua serial Excel */
    const GOC_EXCEL = '1899-12-30';

    /**
     * @param mixed $gt
     * @return int|null so nguyen dang Ymd, null neu khong hieu
     */
    public static function phanTich($gt)
    {
        if ($gt === null) {
            return null;
        }

        $s = trim((string) $gt);

        if ($s === '') {
            return null;
        }

        if (is_numeric($s)) {
            $so = (float) $s;

            // Serial Excel va Ymd khong chong lan: serial cao nhat 80.000, Ymd thap nhat
            // 19.000.101.
            if ($so >= 1 && $so <= self::SERIAL_TOI_DA) {
                return self::tuSerialExcel($so);
            }

            if (preg_match('/^\d{8}$/', $s)) {
                return self::hopLe(
                    (int) substr($s, 0, 4),
                    (int) substr($s, 4, 2),
                    (int) substr($s, 6, 2)
                );
            }

            return null;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
            return self::hopLe((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {
            return self::hopLe((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $s, $m)) {
            return self::hopLe((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    /**
     * Moc thoi gian HIS dang YmdHis -> Ymd.
     *
     * @return int|null
     */
    public static function tuMocHis($moc)
    {
        $s = trim((string) $moc);

        if (!preg_match('/^\d{14}$/', $s)) {
            return null;
        }

        return self::hopLe(
            (int) substr($s, 0, 4),
            (int) substr($s, 4, 2),
            (int) substr($s, 6, 2)
        );
    }

    /**
     * Dong danh muc con hieu luc tai $ngayYmd khong.
     *
     * $ngayYmd rong -> khong loc (tra true). Lop goi tu quyet dinh co bo qua dong do khong.
     *
     * @param mixed $tuNgay
     * @param mixed $denNgay
     * @param int|null $ngayYmd
     * @return bool
     */
    public static function conHieuLuc($tuNgay, $denNgay, $ngayYmd)
    {
        if (empty($ngayYmd)) {
            return true;
        }

        $tu = self::phanTich($tuNgay);

        if ($tu !== null && $ngayYmd < $tu) {
            return false;
        }

        $den = self::phanTich($denNgay);

        if ($den !== null && $ngayYmd > $den) {
            return false;
        }

        return true;
    }

    protected static function tuSerialExcel($so)
    {
        $ngay = new \DateTime(self::GOC_EXCEL);
        $ngay->modify('+' . (int) floor($so) . ' days');

        return (int) $ngay->format('Ymd');
    }

    protected static function hopLe($nam, $thang, $ngay)
    {
        if ($nam < 1900 || $nam > 2999 || !checkdate($thang, $ngay, $nam)) {
            return null;
        }

        return $nam * 10000 + $thang * 100 + $ngay;
    }
}
