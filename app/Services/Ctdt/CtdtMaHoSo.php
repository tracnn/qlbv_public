<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Suy khoa nghiep vu cua MOT ho so.
 *
 * Khoa nay quyet dinh viec GHI DE khi nap lai, nen sai o day hong theo hai kieu va ca hai
 * deu im lang:
 *   - khoa qua HEP  -> nap lai de ra ban ghi thu hai, khong ai ghi de ai
 *   - khoa qua RONG -> hai ho so khac nhau bi coi la mot, ban sau xoa ban truoc
 *
 * Thu tu uu tien:
 *   1. Khoa nghiep vu cua chung tu DAU TIEN co (MA_YTE / MA_GBT / MA_GCS)
 *   2. Id cua goi kem CHI SO ho so
 *
 * VI SAO PHAI KEM CHI SO o buoc 2: mot tep HSCHUNGTU co nhieu HOSO dung CHUNG mot Id cua
 * THONGTINHOSO. Neu khoa lui chi la Id thi hai ho so cung thieu MA_YTE trong cung mot tep
 * se nhan cung khoa, va cai thu hai ghi de cai thu nhat NGAY TRONG mot lan nap.
 *
 * VI SAO KHONG bia khoa tu MA_THE + NGAY_VAO khi ca hai buoc deu can: rui ro nguoc lai va
 * nang hon. Nem de nguoi van hanh biet ngay tai buoc nap.
 */
class CtdtMaHoSo
{
    /**
     * @param array       $chungTu    Mang ['loai_ho_so' =>, 'noi_dung' =>] cua MOT ho so
     * @param string|null $idGoi      Thuoc tinh Id cua the mang chu ky
     * @param int         $chiSoHoSo  Vi tri ho so trong tep, dem tu 1
     * @return string
     * @throws KhongXacDinhDuocMaHoSoException
     */
    public static function cua(array $chungTu, $idGoi, $chiSoHoSo)
    {
        foreach ($chungTu as $ct) {
            if (!isset($ct['loai_ho_so']) || !CtdtLoaiRegistry::co($ct['loai_ho_so'])) {
                // Loai la duoc CtdtImporter bat rieng. Tu choi ca ho so tai day chi vi mot
                // loai la se lam mat mot ho so von hop le.
                continue;
            }

            $lop = CtdtLoaiRegistry::cho($ct['loai_ho_so']);
            $ma  = $lop::maChungTu($ct['noi_dung']);

            if ($ma !== null && $ma !== '') {
                self::kiemDoDai($ma, $chiSoHoSo);

                return $ma;
            }
        }

        if ($idGoi !== null && $idGoi !== '') {
            $khoa = $idGoi . '#' . $chiSoHoSo;
            self::kiemDoDai($khoa, $chiSoHoSo);

            return $khoa;
        }

        throw new KhongXacDinhDuocMaHoSoException(
            'Ho so #' . $chiSoHoSo . ': khong co MA_YTE/MA_GBT/MA_GCS va goi cung khong co Id'
        );
    }

    /**
     * Cot ctdt_ho_so.ma_ho_so la varchar(100). MA_YTE/MA_GBT/MA_GCS den tu XML nguoi dung
     * tai len - du lieu ben ngoai, khong duoc tin la ngan.
     *
     * @throws KhongXacDinhDuocMaHoSoException khi khoa suy ra dai qua 100 ky tu
     */
    private static function kiemDoDai($khoa, $chiSoHoSo)
    {
        if (strlen($khoa) > 100) {
            throw new KhongXacDinhDuocMaHoSoException(
                'Ho so #' . $chiSoHoSo . ': ma ho so suy ra dai ' . strlen($khoa)
                . ' ky tu, vuot qua gioi han 100 ky tu'
            );
        }
    }
}
