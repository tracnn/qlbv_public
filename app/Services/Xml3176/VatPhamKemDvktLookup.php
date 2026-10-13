<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Illuminate\Database\QueryException;

/**
 * Nap bang tra vat pham -> DVKT me mot lan moi tien trinh, lam moi sau TTL_GIAY de tien
 * trinh hang doi chay lau van thay thay doi tu lenh hoc/nap ma khong can khoi dong lai.
 */
class VatPhamKemDvktLookup
{
    const TTL_GIAY = 600;

    private static $bang = null;
    private static $napLuc = 0;

    public static function bang(): array
    {
        if (self::$bang === null || time() - self::$napLuc > self::TTL_GIAY) {
            try {
                $dong = Xml3176VatPhamKemDvkt::where('hieu_luc', true)
                    ->get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt']);
            } catch (QueryException $e) {
                // Bang chua migrate (trien khai chua xong): R1 im lang, R2 van chay.
                $dong = [];
            }
            self::$bang = self::tuDong($dong);
            self::$napLuc = time();
        }

        return self::$bang;
    }

    public static function tuDong($dong): array
    {
        $bang = ['VTYT' => [], 'THUOC' => []];
        foreach ($dong as $d) {
            $d = is_array($d) ? (object) $d : $d;
            if (isset($d->hieu_luc) && !$d->hieu_luc) {
                continue;
            }
            $khoa = trim((string) $d->ma_vat_pham) . '|' . (string) $d->ten_khop;
            $bang[$d->loai][$khoa][VatPhamKemDvktKey::chuanHoaMaDvkt($d->ma_dvkt)] = (string) ($d->ten_dvkt ?? '');
        }

        return $bang;
    }

    public static function xoaBoNho(): void
    {
        self::$bang = null;
        self::$napLuc = 0;
    }
}
