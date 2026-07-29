<?php

namespace App\Services\BHYT;

use DB;
use Cache;
use Illuminate\Support\Facades\Log;

/**
 * Danh sach co so kham chua benh de chon khi nhap danh muc.
 *
 * Khoa la MA CSKCB (his_branch.hein_medi_org_code), khong phai branch_id: BHXH cap danh
 * muc theo ma CSKCB, va NHIEU co so HIS co the dung chung mot ma - vi du Bach Mai (id=1)
 * va Bach Mai CS2 (id=21) deu la 01929. Lay branch_id lam khoa se buoc nhap trung danh muc.
 *
 * Chi lay co so DANG HOAT DONG (is_active = 1, is_delete = 0) - day la danh sach de NGUOI
 * DUNG CHON khi nhap danh muc moi.
 *
 * KHONG ap bo loc nay o cho leftJoin('his_branch') trong HisOrderSource: ho so cu thuoc co
 * so da ngung hoat dong van phai doc duoc ma CSKCB, neu loc o do thi ma thanh rong va ho so
 * mat luon phan kiem theo co so.
 *
 * HIS hong thi tra mang RONG chu khong nem loi: man nhap danh muc van phai dung duoc, chi
 * la khong chon duoc co so.
 */
class DanhSachCoSo
{
    const KHOA_CACHE = 'bhyt.danh_sach_co_so';

    /** Phut giu cache; danh sach co so rat it doi */
    const PHUT = 60;

    /**
     * @return array ma_cskcb => nhan hien thi, sap theo ma
     */
    public static function danhSach()
    {
        return Cache::remember(self::KHOA_CACHE, self::PHUT, function () {
            return self::doc();
        });
    }

    /** Doc thang tu HIS, khong qua cache. */
    public static function doc()
    {
        try {
            $rows = DB::connection(config('order_check.his_connection'))
                ->table('his_branch')
                ->where('is_delete', 0)
                ->where('is_active', 1)
                ->whereNotNull('hein_medi_org_code')
                ->select('hein_medi_org_code', 'branch_name')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Khong doc duoc danh sach co so tu HIS: ' . $e->getMessage());

            return [];
        }

        return self::gom($rows);
    }

    /**
     * Gom cac co so HIS cung ma CSKCB thanh MOT lua chon.
     *
     * Ham thuan de kiem duoc: nhan danh sach dong, tra ma => nhan.
     *
     * @param iterable $rows moi phan tu co hein_medi_org_code va branch_name
     * @return array
     */
    public static function gom($rows)
    {
        $ra = [];

        foreach ($rows as $r) {
            $r = (array) $r;
            $ma = isset($r['hein_medi_org_code']) ? trim((string) $r['hein_medi_org_code']) : '';
            $ten = isset($r['branch_name']) ? trim((string) $r['branch_name']) : '';

            if ($ma === '') {
                continue;
            }

            if (!isset($ra[$ma])) {
                $ra[$ma] = [];
            }

            if ($ten !== '' && !in_array($ten, $ra[$ma], true)) {
                $ra[$ma][] = $ten;
            }
        }

        $nhan = [];

        foreach ($ra as $ma => $ten) {
            $nhan[$ma] = $ma . (empty($ten) ? '' : ' — ' . implode(' / ', $ten));
        }

        ksort($nhan);

        return $nhan;
    }
}
