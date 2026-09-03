<?php

namespace App\Services\Mcct;

/**
 * Nguong mien cung chi tra: 6 thang luong co so tai thoi diem tra cuu.
 *
 * Ham THUAN - nhan bang luong lam THAM SO chu khong tu doc config, giong cach CoSoTraCuu
 * nhan $dsCoSo. Nho vay kiem duoc moi moc luong ma khong phai sua cau hinh that.
 */
class NguongMienCungChiTra
{
    /**
     * Muc luong co so co hieu luc tai mot ngay.
     *
     * Tra 0 khi ngay nam TRUOC moi moc. KHONG roi ve moc dau tien: roi ve nghia la bia ra
     * mot nguong cho khoang thoi gian chua khai, va cai sai do khong co dau hieu gi.
     *
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong], config('mcct.luong_co_so')
     * @return int
     */
    public static function luongCoSoTaiNgay($ngay, array $bangLuong)
    {
        $ngay = trim((string) $ngay);
        $muc = 0;

        // Duyet theo thu tu moc tang dan, giu lai moc cuoi cung con <= ngay can tra.
        // Sap lai tai day chu khong tin thu tu nguoi khai go trong config.
        ksort($bangLuong);

        foreach ($bangLuong as $moc => $gia) {
            if (strcmp((string) $moc, $ngay) <= 0) {
                $muc = (int) $gia;
            }
        }

        return $muc;
    }

    /**
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong]
     * @param int $soThang config('mcct.so_thang_luong_co_so')
     * @return float
     */
    public static function nguong($ngay, array $bangLuong, $soThang)
    {
        return (float) (self::luongCoSoTaiNgay($ngay, $bangLuong) * (int) $soThang);
    }

    /**
     * NĐ 188/2025/NĐ-CP dung cau chu "LON HON 6 thang luong co so" - dau > chu khong phai
     * >=. Bang dung nguong la CHUA du dieu kien.
     *
     * Nguong 0 (bang luong chua khai) luon tra false: khong co nguong thi khong ket luan
     * duoc, va ket luan "ai cung du" la sai theo huong te nhat.
     *
     * @param float $luyKe
     * @param float $nguong
     * @return bool
     */
    public static function duDieuKien($luyKe, $nguong)
    {
        if ((float) $nguong <= 0) {
            return false;
        }

        return (float) $luyKe > (float) $nguong;
    }
}
