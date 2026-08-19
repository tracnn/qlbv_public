<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loai\Ct03;

/**
 * Diem tra cuu DUY NHAT tu gia tri LOAIHOSO sang lop loai chung tu.
 *
 * VI SAO PHAI DOI CHIEU CHEO THE GOC: gia tri LOAIHOSO KHONG luon trung ten the goc
 * ben trong base64 - GIAYDIEUTRINOITRU chua <CTGiayDieuTriNoiTru>. Neu lay ten the goc
 * lam khoa tra (cach tu nhien nhat) thi ba loai lech nay roi vao nhanh "loai la" mot
 * cach IM LANG. Doi chieu hai chieu de sai lech lo ra ngay o buoc nap.
 */
class CtdtLoaiRegistry
{
    /**
     * @return array [gia tri LOAIHOSO => ten lop]
     */
    public static function tatCa()
    {
        return [
            'CT03' => Ct03::class,
        ];
    }

    public static function co($loaiHoSo)
    {
        return is_string($loaiHoSo) && array_key_exists($loaiHoSo, self::tatCa());
    }

    /**
     * @throws \InvalidArgumentException khi loai khong co trong dang ky
     */
    public static function cho($loaiHoSo)
    {
        if (!self::co($loaiHoSo)) {
            throw new \InvalidArgumentException('Loai ho so khong nam trong dang ky: ' . $loaiHoSo);
        }

        return self::tatCa()[$loaiHoSo];
    }

    /**
     * @throws \RuntimeException khi the goc thuc te khac the goc khai trong lop loai
     */
    public static function xacNhanTheGoc($loaiHoSo, $theGocThucTe)
    {
        $lop = self::cho($loaiHoSo);
        $mongDoi = $lop::theGoc();

        if ($mongDoi !== $theGocThucTe) {
            throw new \RuntimeException(
                'LOAIHOSO ' . $loaiHoSo . ' mong doi the goc <' . $mongDoi
                . '> nhung noi dung la <' . $theGocThucTe . '>'
            );
        }
    }

    /**
     * @return array [gia tri LOAIHOSO => ten lop] cua mot dich vu
     */
    public static function cuaDichVu($dichVu)
    {
        $ketQua = [];

        foreach (self::tatCa() as $loai => $lop) {
            if ($lop::dichVu() === $dichVu) {
                $ketQua[$loai] = $lop;
            }
        }

        return $ketQua;
    }
}
