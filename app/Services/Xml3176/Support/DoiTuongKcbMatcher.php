<?php

namespace App\Services\Xml3176\Support;

/**
 * Xac dinh mot ho so co thuoc dien KHONG can ra loi hay khong, can cu MA_DOITUONG_KCB.
 *
 * Boi canh: module XML3176 nhan ca ho so khong phai BHYT (ho so dich vu, ma doi tuong 9).
 * Bo quy tac ra loi duoc viet cho ho so BHYT, nen ap len ho so dich vu chi sinh nhieu:
 * do tren du lieu that, 49/51 ho so la doi tuong 9 va chung sinh 97% tong so loi.
 *
 * Ma doi tuong KCB theo bo ma DMDC dung dang phan cap co dau cham ('1.1', '1.17'), nen
 * '9' phai bao ca nhanh '9.*'. KHONG dung strpos($ma, $tienTo) === 0 thuan: cach do se
 * nuot ca '91', mot ma hoan toan khac.
 */
class DoiTuongKcbMatcher
{
    /**
     * @param  string|null $maDoiTuong Gia tri MA_DOITUONG_KCB cua ho so
     * @param  array       $danhSach   Cac ma nam ngoai dien ra loi (config)
     */
    public static function khongCanKiem($maDoiTuong, array $danhSach): bool
    {
        $ma = trim((string) $maDoiTuong);

        // Thieu can cu thi VAN ra loi: bo nham la mat bao ve trong im lang, con ra nham
        // chi la nhieu nhin thay duoc.
        if ($ma === '') {
            return false;
        }

        foreach ($danhSach as $tienTo) {
            $tienTo = trim((string) $tienTo);

            if ($tienTo === '') {
                continue;
            }

            if ($ma === $tienTo || strpos($ma, $tienTo . '.') === 0) {
                return true;
            }
        }

        return false;
    }
}
