<?php

namespace App\Services\Tt12;

use App\Services\Tt12\Mau\Mau01;
use App\Services\Tt12\Mau\Mau02;
use App\Services\Tt12\Mau\Mau03;
use App\Services\Tt12\Mau\Mau04;
use App\Services\Tt12\Mau\Mau05;
use App\Services\Tt12\Mau\Mau06;

/**
 * Diem tra cuu DUY NHAT tu ma mau sang lop dac ta.
 *
 * VI SAO MOT DIEM DUY NHAT: trong XML3176, nghiep vu tung duoc cai HAI lan - mot lan
 * trong controller, mot lan trong lenh console - va hai ban DA LECH NHAU. Moi noi can
 * biet "mau nay co nhung cot gi" deu hoi o day.
 */
class Tt12MauRegistry
{
    /** @return array [ma mau => ten lop] */
    public static function tatCa()
    {
        return array(
            'MAU_01' => Mau01::class,
            'MAU_02' => Mau02::class,
            'MAU_03' => Mau03::class,
            'MAU_04' => Mau04::class,
            'MAU_05' => Mau05::class,
            'MAU_06' => Mau06::class,
        );
    }

    /** @return bool */
    public static function co($mau)
    {
        return is_string($mau) && array_key_exists($mau, self::tatCa());
    }

    /**
     * @return string ten lop dac ta
     * @throws \InvalidArgumentException khi mau khong co trong dang ky
     */
    public static function cho($mau)
    {
        if (!self::co($mau)) {
            throw new \InvalidArgumentException('Mau khong nam trong dang ky: ' . (string) $mau);
        }

        $tatCa = self::tatCa();

        return $tatCa[$mau];
    }

    /**
     * Nhan dien mau tu hang tieu de cua tep Excel.
     *
     * So khop CHINH XAC tap ten cot, khong phai "chua mot vai cot dac trung". MAU_03 va
     * MAU_04 chia nhau rat nhieu ten cot (TT_THAU, LOAI_THAU, HT_THAU, NUOC_SX...), nen
     * nhan dien theo vai cot dac trung se gan nham mau va toan bo tep vao sai danh muc.
     *
     * Bo qua cot rong o duoi hang tieu de: Excel thuong tra ve mot vai o null o cuoi.
     *
     * @param array $header ten cot doc tu hang dau tien
     * @return string|null ma mau, hoac null neu khong mau nao khop
     */
    public static function nhanDien(array $header)
    {
        $doc = array();

        foreach ($header as $o) {
            $o = strtoupper(trim((string) $o));

            if ($o !== '') {
                $doc[] = $o;
            }
        }

        sort($doc);

        foreach (self::tatCa() as $ma => $lop) {
            $mongDoi = $lop::tenThe();

            if ($lop::cotCon() !== array()) {
                $mongDoi = array_merge($mongDoi, $lop::tenCotExcelCon());
            }

            sort($mongDoi);

            if ($doc === $mongDoi) {
                return $ma;
            }
        }

        return null;
    }
}
