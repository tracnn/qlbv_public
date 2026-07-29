<?php

namespace App\Services\BHYT;

/**
 * Loc danh sach ho so XML3176 theo ma co so KCB.
 *
 * Tach rieng vi BHYTXml3176Controller::fetchData co BA nhanh dung truy van khac nhau
 * (theo ma ho so / theo ma benh nhan / theo khoang ngay). Neu chi them dieu kien vao mot
 * nhanh thi hai nhanh kia bo qua bo loc IM LANG: van ra ket qua, chi la sai pham vi, khong
 * co dau hieu gi bao.
 */
class LocCoSo
{
    /**
     * Ma dung de loc, hoac chuoi rong nghia la KHONG loc.
     *
     * Ham THUAN. Ma khong nam trong danh sach thi coi nhu khong loc thay vi nem loi: day
     * la man DANH SACH, khong phai thao tac ghi.
     *
     * @param string|null $ma
     * @param array $danhSach mang ma => nhan
     * @return string
     */
    public static function maHopLe($ma, array $danhSach)
    {
        $ma = trim((string) $ma);

        if ($ma === '' || !array_key_exists($ma, $danhSach)) {
            return '';
        }

        return $ma;
    }

    /**
     * Ap dieu kien loc vao query neu ma hop le.
     *
     * @param mixed $query Eloquent Builder hoac Query Builder
     * @param string|null $ma
     * @param array $danhSach mang ma => nhan
     * @param string $cot ten cot chua ma co so
     * @return mixed chinh $query
     */
    public static function ap($query, $ma, array $danhSach, $cot = 'ma_cskcb')
    {
        $ma = self::maHopLe($ma, $danhSach);

        if ($ma !== '') {
            $query->where($cot, $ma);
        }

        return $query;
    }
}
