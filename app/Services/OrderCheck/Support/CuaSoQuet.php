<?php

namespace App\Services\OrderCheck\Support;

/**
 * Cua so quet co chan tren, va quy tac day moc sau moi luot.
 *
 * Vi sao can: Laravel sinh SQL dang
 *   select * from (select rownum rn, t1.* from (... order by id) t1) where rn <= 500
 * Truy van TRONG CUNG khong co gioi han, nen Oracle noi va sap xep MOI dong sau moc roi
 * moi cat 500 o tang ngoai. Do tren production, limit giu nguyen 500:
 *   ton    10.000 dong ->     68 ms
 *   ton   100.000 dong ->    582 ms
 *   ton 1.000.000 dong ->  4.849 ms
 *   ton 5.000.000 dong -> 21.356 ms
 * Tuyen tinh voi KHOANG TON, khong lien quan limit. Ton cang lon, duoi kip cang cham.
 *
 * Chan tren lam tap phai sap xep bi chan cung, thoi gian moi luot thanh hang so.
 *
 * Ham THUAN de kiem duoc.
 */
class CuaSoQuet
{
    /**
     * Cuoi cua so quet.
     *
     * @param int $moc   moc hien tai (last_id)
     * @param int $cuaSo do rong cua so; <= 0 nghia la KHONG chan
     * @return int 0 nghia la khong chan
     */
    public static function ketThuc($moc, $cuaSo)
    {
        $cuaSo = (int) $cuaSo;

        if ($cuaSo <= 0) {
            return 0;
        }

        return (int) $moc + $cuaSo;
    }

    /**
     * Moc moi sau mot luot quet.
     *
     * Lay DU limit  -> cua so chua duyet het -> chi tien toi id lon nhat da lay.
     * Lay IT hon    -> cua so da duyet het   -> nhay toi cuoi cua so.
     *
     * Ve thu hai la thu chua cai bay: cua so rong ma khong day moc thi bo quet dung im
     * vinh vien, im lang, khong loi nao bao ra.
     *
     * @param int $moc          moc hien tai
     * @param int $soDongLay    so dong lo vua tra ve
     * @param int $limit        gioi han moi luot
     * @param int $maxIdTrongLo id lon nhat trong lo (0 neu lo rong)
     * @param int $cuoiCuaSo    ket qua cua ketThuc(); 0 nghia la khong chan
     * @return int khong bao gio nho hon $moc
     */
    public static function mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo)
    {
        $moc = (int) $moc;
        $cuoiCuaSo = (int) $cuoiCuaSo;

        if ($cuoiCuaSo <= 0 || (int) $soDongLay >= (int) $limit) {
            return max($moc, (int) $maxIdTrongLo);
        }

        return max($moc, $cuoiCuaSo);
    }
}
