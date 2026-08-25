<?php

namespace App\Services\Tt12;

use Carbon\Carbon;
use App\Models\BHYT\Tt12\Tt12HoSo;

/**
 * Sinh ma ho so nghiep vu: TT12_<MAU>_<MACSKCB>_<YYYYMMDD>_<STT 3 chu so>.
 *
 * VI SAO KHONG DUNG GUID: ma nay hien tren man hinh va duoc doc len dien thoai khi doi
 * soat voi co quan BHXH. Mot chuoi 36 ky tu ngau nhien khong doc duoc.
 *
 * VI SAO CO SO THU TU TRONG NGAY: mot co so co the nap lai cung mot danh muc nhieu lan
 * trong ngay (sua roi nap lai). Moi lan la mot ho so RIENG vi moi lan se co MaGD rieng.
 */
class Tt12MaHoSo
{
    /**
     * Ham THUAN de kiem duoc.
     *
     * @param string $mau       MAU_01..MAU_06
     * @param string $maCskcb
     * @param Carbon $thoiDiem
     * @param int    $soThuTu   thu tu trong ngay, bat dau tu 1
     * @return string
     */
    public static function sinh($mau, $maCskcb, Carbon $thoiDiem, $soThuTu)
    {
        return 'TT12_' . $mau . '_' . $maCskcb . '_'
            . $thoiDiem->format('Ymd') . '_'
            . str_pad((string) $soThuTu, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Sinh ma ke tiep con trong cho mot co so trong ngay.
     *
     * Vong lap chu khong COUNT(*) + 1: dem so ho so trong ngay roi cong mot se dung lai
     * ma cu neu mot ho so giua chung da bi xoa. Vong lap tim khoang trong dau tien.
     *
     * @return string
     */
    public static function keTiep($mau, $maCskcb, Carbon $thoiDiem = null)
    {
        $thoiDiem = $thoiDiem ?: Carbon::now();

        for ($i = 1; $i <= 999; $i++) {
            $ma = self::sinh($mau, $maCskcb, $thoiDiem, $i);

            if (!Tt12HoSo::where('ma_ho_so', $ma)->exists()) {
                return $ma;
            }
        }

        throw new \RuntimeException(
            'Da het so thu tu trong ngay cho ' . $mau . '/' . $maCskcb
            . ' (999 ho so). Kiem tra xem co vong lap nap tu dong nao khong.'
        );
    }
}
