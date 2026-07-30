<?php

namespace App\Services\BHYT;

/**
 * Danh sach co so KCB de CHON khi tra cuu the BHYT thu cong.
 *
 * Khac voi DanhSachCoSo (liet ke moi co so dang hoat dong trong HIS, dung lam BO LOC): o day
 * nguon la khoa cua BHYT_CO_SO, tuc la nhung co so DA KHAI TAI KHOAN cong BHXH.
 *
 * Vi sao khong lay danh sach HIS: co so chua khai tai khoan thi chon vao chac chan loi. Mot o
 * chon co lua chon KHONG BAO GIO dung duoc la bay nguoi dung - ho phai thu moi biet, va thong
 * bao loi luc do khong noi duoc la do cau hinh thieu hay do cong hong.
 *
 * Co so khai trong config ma HIS khong co van hien (ma tran): no tra duoc that, chi la khong
 * lay duoc ten dep.
 *
 * Ham THUAN de kiem duoc.
 *
 * CANH BAO KIEU KHOA: PHP tu ep khoa mang dang so ve int, nen '37470' thanh int 37470 con
 * '01929' giu nguyen chuoi (co so 0 dau). Mang tra ve co khoa LAN LON hai kieu. Khong sua
 * duoc - do la ngu nghia cua PHP, va DanhSachCoSo::gom() san co cung nhu vay.
 * Hau qua: KHONG duoc so sanh khoa bang toan tu nghiem ngat kieu (=== hay in_array(..., true)).
 * Dung maDangChuoi() khi can danh sach ma de so sanh hoac de dung lam luat validation.
 */
class CoSoTraCuu
{
    /**
     * @param array $dsCoSo config('organization.BHYT_CO_SO')
     * @param array $nhanHis DanhSachCoSo::danhSach() - ma => nhan; rong neu HIS hong
     * @return array ma => nhan, sap theo ma (khoa co the la int, xem canh bao tren)
     */
    public static function danhSach(array $dsCoSo, array $nhanHis)
    {
        $ra = [];

        foreach ($dsCoSo as $ma => $khoi) {
            $ma = trim((string) $ma);

            if ($ma === '') {
                continue;
            }

            // HIS hong thi $nhanHis rong - van phai chon duoc co so, chi la nhan tro thanh ma
            // tran. Man tra cuu khong duoc phu thuoc vao viec doc duoc HIS.
            $nhan = isset($nhanHis[$ma]) ? trim((string) $nhanHis[$ma]) : '';

            $ra[$ma] = $nhan !== '' ? $nhan : $ma;
        }

        ksort($ra);

        return $ra;
    }

    /**
     * Danh sach ma duoi dang CHUOI, dung de so sanh va de dung lam luat validation `in:`.
     *
     * Can ham rieng vi array_keys() tra ve khoa lan lon int/string (xem canh bao o dau lop).
     *
     * @return array danh sach ma, moi phan tu la string
     */
    public static function maDangChuoi(array $danhSach)
    {
        return array_map('strval', array_keys($danhSach));
    }

    /** Danh sach dung cau hinh that va HIS that. */
    public static function tuCauHinh()
    {
        return self::danhSach(
            (array) config('organization.BHYT_CO_SO', []),
            (array) DanhSachCoSo::danhSach()
        );
    }
}
