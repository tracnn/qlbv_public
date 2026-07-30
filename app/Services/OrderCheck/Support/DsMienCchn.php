<?php

namespace App\Services\OrderCheck\Support;

/**
 * Danh sach tai khoan duoc MIEN kiem tra chung chi hanh nghe.
 *
 * Vi sao can: mot so "nguoi thuc hien" trong HIS khong phai nguoi ma la tai khoan tich hop
 * may moc - mitalab (may xet nghiem), vietrad (chan doan hinh anh), sys (he thong). Chung
 * khong the co CCHN nen quy tac B_DOCTOR_NO_PRACTICE_CERT bao vi pham cho chung la vo nghia.
 *
 * Do ngay 30/07/2026: 5.422 vi pham cua quy tac nay thi mitalab 4.310, vietrad 1.066,
 * sys 4 - tuc 99,2% la nhieu. Phan con lai (ntdh3, vttq2 va hai nguoi khac) deu la NGUOI
 * THAT thieu CCHN trong HIS, tuc phat hien dung, khong duoc mien.
 *
 * Vi sao dung DANH SACH TUONG MINH chu khong tu nhan dien: da thu quy tac "tdl_username =
 * loginname" thi ra 32 tai khoan, lan lon ca tai khoan thu nghiem (demo1, ddtest), tai
 * khoan phong ban (noitru, vss - co diploma 'CNTT'), va admin/fpt. Tu dong se IM LANG bo
 * qua nhung thu khong nen bo, va nguoi bao tri sau khong co cach nao biet ai dang duoc mien.
 *
 * Ham THUAN de kiem duoc.
 */
class DsMienCchn
{
    /**
     * Doc CSV thanh mang loginname da chuan hoa.
     *
     * @param string|null $csv
     * @return array loginname da ha thuong, da cat khoang trang, bo phan tu rong
     */
    public static function doc($csv)
    {
        $csv = trim((string) $csv);

        if ($csv === '') {
            return [];
        }

        $ra = [];

        foreach (explode(',', $csv) as $ten) {
            $ten = mb_strtolower(trim($ten));

            if ($ten !== '') {
                $ra[] = $ten;
            }
        }

        return $ra;
    }

    /**
     * Loginname co duoc mien kiem tra CCHN khong.
     *
     * So khop KHONG phan biet hoa thuong va cat khoang trang ca hai ve: HIS co tai khoan
     * viet hoa lan lon (BHXHConnector, BMCS, PACS) nen so khop chat se bo sot IM LANG.
     *
     * Chuan hoa CA HAI VE: neu chi chuan hoa mot ve, nguoi dung truyen thang danh sach chua
     * qua doc() se bi bo sot IM LANG.
     *
     * @param string|null $loginname
     * @param array $ds danh sach, co hay chua qua doc()
     * @return bool
     */
    public static function duocMien($loginname, array $ds)
    {
        if (empty($ds)) {
            return false;
        }

        $loginname = mb_strtolower(trim((string) $loginname));

        if ($loginname === '') {
            return false;
        }

        foreach ($ds as $ten) {
            if ($loginname === mb_strtolower(trim((string) $ten))) {
                return true;
            }
        }

        return false;
    }
}
