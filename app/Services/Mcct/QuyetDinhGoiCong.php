<?php

namespace App\Services\Mcct;

/**
 * Quyet dinh mot loi goi API co duoc phep cham toi cong BHXH hay khong.
 *
 * VI SAO CAN: cong co danh sach tai khoan bi han che tra cuu, nen so luot goi la tai nguyen
 * co han. He thong goi API nam ngoai tam kiem soat cua qlbv - mot vong lap hong ben do co
 * the lam tai khoan cua ca benh vien bi khoa.
 *
 * Ham THUAN: nhan thoi diem lam tham so chu khong tu doc dong ho, nen kiem duoc moc bien ma
 * khong phai cho that 15 phut.
 */
class QuyetDinhGoiCong
{
    /**
     * @param bool $lamMoi ben goi co yeu cau goi cong that hay khong
     * @param string|null $traLucGanNhat 'Y-m-d H:i:s' cua lan tra gan nhat; null neu chua tra
     * @param string $bayGio 'Y-m-d H:i:s'
     * @param int $khauDoGiay 0 = tat chan
     * @return array ['goi' => bool, 'con_lai' => int so giay con phai cho]
     */
    public static function nen($lamMoi, $traLucGanNhat, $bayGio, $khauDoGiay)
    {
        if (!$lamMoi) {
            return ['goi' => false, 'con_lai' => 0];
        }

        $khauDo = (int) $khauDoGiay;

        if ($khauDo <= 0) {
            return ['goi' => true, 'con_lai' => 0];
        }

        $moc = self::mocThoiGian($traLucGanNhat);
        $now = self::mocThoiGian($bayGio);

        // Chua tung tra, hoac moc khong doc duoc: khong co gi de chan. Doan bua mot moc con
        // te hon la khong chan - no se chan nhung lan goi hop le mot cach ngau nhien.
        if ($moc === null || $now === null) {
            return ['goi' => true, 'con_lai' => 0];
        }

        $daQua = $now - $moc;

        // Moc nam o TUONG LAI (dong ho lech hoac du lieu hong): coi nhu vua tra xong. Cho goi
        // la mo duong cho mot dong ho lech lam thung ca co che chan.
        if ($daQua < 0) {
            return ['goi' => false, 'con_lai' => $khauDo];
        }

        if ($daQua >= $khauDo) {
            return ['goi' => true, 'con_lai' => 0];
        }

        return ['goi' => false, 'con_lai' => $khauDo - $daQua];
    }

    /** @return int|null dau thoi gian Unix; null neu khong doc duoc */
    private static function mocThoiGian($chuoi)
    {
        $chuoi = trim((string) $chuoi);

        if ($chuoi === '') {
            return null;
        }

        $t = strtotime($chuoi);

        return $t === false ? null : $t;
    }
}
