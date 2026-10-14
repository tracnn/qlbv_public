<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Illuminate\Support\Facades\Schema;

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
            // Chi bang chua migrate (trien khai chua xong) moi coi la rong: R1 im lang, R2 van chay.
            // Moi loi CSDL khac (deadlock, mat ket noi...) phai lan ra de job loi va duoc thu lai,
            // khong duoc nuot roi cache bang rong lam tat quy tac am tham.
            $dong = Schema::hasTable('xml3176_vat_pham_kem_dvkt')
                ? Xml3176VatPhamKemDvkt::where('hieu_luc', true)
                    ->get(['loai', 'ma_vat_pham', 'ten_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt'])
                : [];
            $batBuoc = Schema::hasTable('xml3176_vpkd_dvkt_bat_buoc')
                ? \App\Models\BHYT\Xml3176VpkdDvktBatBuoc::where('hieu_luc', true)->get(['ma_dvkt', 'ten_dvkt'])
                : [];
            self::$bang = self::tuDong($dong, $batBuoc);
            self::$napLuc = time();
        }

        return self::$bang;
    }

    public static function tuDong($dong, $batBuoc = []): array
    {
        $bang = ['VTYT' => [], 'THUOC' => [], 'DVKT_BAT_BUOC' => [], 'TEN_VAT_PHAM' => []];
        foreach ($dong as $d) {
            $d = is_array($d) ? (object) $d : $d;
            if (isset($d->hieu_luc) && !$d->hieu_luc) {
                continue;
            }
            $khoa = trim((string) $d->ma_vat_pham) . '|' . (string) $d->ten_khop;
            $bang[$d->loai][$khoa][VatPhamKemDvktKey::chuanHoaMaDvkt($d->ma_dvkt)] = (string) ($d->ten_dvkt ?? '');
            $bang['TEN_VAT_PHAM'][$d->loai . '|' . $khoa] = (string) ($d->ten_vat_pham ?? $d->ma_vat_pham);
        }
        foreach ($batBuoc as $b) {
            $b = is_array($b) ? (object) $b : $b;
            if (isset($b->hieu_luc) && !$b->hieu_luc) {
                continue;
            }
            $bang['DVKT_BAT_BUOC'][VatPhamKemDvktKey::chuanHoaMaDvkt($b->ma_dvkt)] = (string) ($b->ten_dvkt ?? '');
        }

        return $bang;
    }

    public static function xoaBoNho(): void
    {
        self::$bang = null;
        self::$napLuc = 0;
    }
}
