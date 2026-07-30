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
 * QUAN TRONG cho ben goi: max(id) THAT (tham so $maxIdThat cua ketThuc()) phai duoc lay
 * TRUOC khi chay truy van lo. Lay SAU thi dong vua commit xen giua hai truy van se bi
 * nhay qua - mat vinh vien, khong khac gi loi cua so vuot qua du lieu that.
 *
 * Ham THUAN de kiem duoc.
 */
class CuaSoQuet
{
    /**
     * Cuoi cua so quet.
     *
     * Khong bao gio vuot qua $maxIdThat (id lon nhat THAT SU dang ton tai trong bang). Neu
     * khong chan boi gia tri nay: khi bo quet da bat kip duoi bang, $moc + $cuaSo la mot id
     * CHUA TON TAI - nhay toi do tuc la tuyen bo da kiem hang chuc nghin id tuong lai. Bo
     * quet chay moi 60 giay nen moc se chay tron xa hon du lieu that voi toc do $cuaSo/phut,
     * nhanh hon toc do sinh du lieu that, khong bao gio hoi phuc, va hoan toan im lang
     * (scanned = 0 trong lai y het da bat kip).
     *
     * @param int $moc       moc hien tai (last_id)
     * @param int $cuaSo     do rong cua so; <= 0 nghia la KHONG chan
     * @param int $maxIdThat id lon nhat THAT SU dang co trong bang (lay TRUOC khi chay truy
     *                       van lo, xem docblock lop nay)
     * @return int 0 nghia la khong chan
     */
    public static function ketThuc($moc, $cuaSo, $maxIdThat)
    {
        $cuaSo = (int) $cuaSo;

        if ($cuaSo <= 0) {
            return 0;
        }

        return min((int) $moc + $cuaSo, (int) $maxIdThat);
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
