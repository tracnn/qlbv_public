<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Bang tra danh muc nhan vien y te (medical_staffs), nho trong tien trinh va lam moi sau TTL_GIAY.
 * Cau truc: co_du_lieu, macchn/ma_bhxh/so_dinh_danh => [KHOA => nguoi], so_cchn => [phan so => [macchn...]].
 */
class NvytLookup
{
    const TTL_GIAY = 600;

    private static $bang = null;
    private static $napLuc = 0;

    public static function bang(): array
    {
        if (self::$bang !== null && time() - self::$napLuc <= self::TTL_GIAY) {
            return self::$bang;
        }

        try {
            if (!Schema::hasTable('medical_staffs')) {
                return self::tuDong([]);
            }
            $dong = MedicalStaff::query()->get(['macchn', 'ma_bhxh', 'so_dinh_danh', 'ho_ten']);
        } catch (\Throwable $e) {
            // Loi doc danh muc: ghi log, tra bang rong va KHONG nho de lan sau con thu lai.
            Log::error('XML3176 NVYT: khong doc duoc danh muc medical_staffs: ' . $e->getMessage());

            return self::tuDong([]);
        }

        self::$bang = self::tuDong($dong);
        self::$napLuc = time();

        return self::$bang;
    }

    public static function tuDong(iterable $dong): array
    {
        $bang = ['co_du_lieu' => false, 'macchn' => [], 'ma_bhxh' => [], 'so_dinh_danh' => [], 'so_cchn' => []];
        foreach ($dong as $d) {
            $d = is_array($d) ? (object) $d : $d;
            $bang['co_du_lieu'] = true;
            $macchn = trim((string) ($d->macchn ?? ''));
            $nguoi = ['ho_ten' => trim((string) ($d->ho_ten ?? '')), 'macchn' => $macchn];

            foreach (['macchn', 'ma_bhxh', 'so_dinh_danh'] as $cot) {
                $k = self::khoa($d->$cot ?? '');
                if ($k !== '' && !isset($bang[$cot][$k])) {
                    $bang[$cot][$k] = $nguoi;
                }
            }

            if ($macchn !== '') {
                $phanSo = ltrim(explode('/', $macchn)[0], '0');
                if ($phanSo !== '' && ctype_digit($phanSo)) {
                    $bang['so_cchn'][$phanSo][] = $macchn;
                }
            }
        }

        return $bang;
    }

    public static function xoaBoNho(): void
    {
        self::$bang = null;
        self::$napLuc = 0;
    }

    public static function khoa($v): string
    {
        return mb_strtoupper(trim((string) $v), 'UTF-8');
    }
}
