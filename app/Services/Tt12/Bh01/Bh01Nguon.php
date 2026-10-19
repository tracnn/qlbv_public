<?php

namespace App\Services\Tt12\Bh01;

use Illuminate\Support\Facades\DB;

/**
 * Nguon du lieu cua 01/BH: cac dong xml3176_xml1s cua mot (co so, ky) - spec 4.1.
 *
 * VI SAO DB::table CHU KHONG DUNG MODEL Xml3176Xml1: dong tra ve la stdClass nhe, phia sau ep
 * (array) de chup vao bang dong; model keo theo cast/accessor khong can va cham hon tren 20 nghin dong.
 */
class Bh01Nguon
{
    private static $cot_tien = array(
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
    );

    public static function truyVan($maCskcb, array $ky)
    {
        return DB::table('xml3176_xml1s')
            ->where('ma_cskcb', $maCskcb)
            ->where('nam_qt', $ky['nam'])
            ->where('thang_qt', $ky['thang'])
            // Chi ho so BHYT (giong Xml3176LocDanhSach::apTheBhyt): dong the rong khong thuoc 01/BH.
            ->where('ma_the_bhyt', '<>', '')
            ->orderByRaw(self::bieuThucMuc())
            ->orderBy('ngay_ra')
            ->orderBy('ma_lk')
            // Chot thu tu TOAN PHAN: xml3176_xml1s duy nhat theo (ma_lk, stt), con id chot cuoi.
            // Thu tu khong toan phan thi chunk() (LIMIT/OFFSET) co the bo sot hoac lap dong.
            ->orderBy('stt')
            ->orderBy('id');
    }

    /**
     * Duyet cac dong cua truyVan() theo lo, DUNG thu tu cua truyVan().
     *
     * VI SAO KHONG DUNG chunk(): chunk() phan trang bang LIMIT/OFFSET, va voi ORDER BY tren
     * bieu thuc CASE thi MySQL phai sap xep lai CA TAP o moi lo. Do that 19/10/2026 ky 10/2026
     * cua 01929 (21.125 ho so): 37 giay MOT luot, tao ban doc hai luot nen vuot gioi han 60
     * giay va request chet giua chung. Lay danh sach id DA SAP XEP mot lan (~1,4 giay) roi doc
     * tung lo bang khoa chinh (~1 giay ca ky) nhanh hon hon chuc lan.
     *
     * Danh sach id chi la so nguyen: 21 nghin id ton vai tram KB, vua voi PHP 128MB.
     *
     * @param callable $xuLy nhan Collection cac dong stdClass cua mot lo; tra ve false de dung
     */
    public static function docTheoLo($maCskcb, array $ky, $kichThuoc, callable $xuLy)
    {
        $ids = self::truyVan($maCskcb, $ky)->pluck('id')->all();

        foreach (array_chunk($ids, $kichThuoc) as $loId) {
            // whereIn khong giu thu tu: xep lai theo dung vi tri trong danh sach id.
            $theoId = DB::table('xml3176_xml1s')->whereIn('id', $loId)->get()->keyBy('id');

            $lo = collect();
            foreach ($loId as $id) {
                if (isset($theoId[$id])) {
                    $lo->push($theoId[$id]);
                }
            }

            if ($xuLy($lo) === false) {
                return;
            }
        }
    }

    public static function tongHop($maCskcb, array $ky)
    {
        $caseMuc = self::bieuThucMuc();
        $chon = array('COUNT(*) AS so_ho_so');
        foreach (array(1, 2, 3, 4, 5) as $m) {
            $chon[] = 'SUM(CASE WHEN (' . $caseMuc . ') = ' . $m . ' THEN 1 ELSE 0 END) AS muc_' . $m;
        }
        foreach (self::$cot_tien as $cot) {
            // ROUND tung dong truoc khi cong: ban luu tong cua gia tri da lam tron tung dong
            // (Bh01ChuanHoa::tien), cong tho roi moi lam tron se lech va bao "du lieu da doi" gia.
            $chon[] = 'SUM(ROUND(' . $cot . ', 2)) AS ' . $cot;
        }

        $r = DB::table('xml3176_xml1s')
            ->where('ma_cskcb', $maCskcb)
            ->where('nam_qt', $ky['nam'])
            ->where('thang_qt', $ky['thang'])
            ->where('ma_the_bhyt', '<>', '')
            ->selectRaw(implode(', ', $chon))
            ->first();

        $kq = array(
            'so_ho_so' => (int) $r->so_ho_so,
            'theo_muc' => array(
                1 => (int) $r->muc_1, 2 => (int) $r->muc_2,
                3 => (int) $r->muc_3, 4 => (int) $r->muc_4,
                'khac' => (int) $r->muc_5,
            ),
        );
        foreach (self::$cot_tien as $cot) {
            $kq[$cot] = round((float) $r->$cot, 2);
        }

        return $kq;
    }

    /**
     * CASE ma_loai_kcb -> muc I..IV, sinh tu Bh01DacTa::muc() de khong lech quy tac xep muc.
     * Dung CASE thuan de chay ca SQLite (test) lan MySQL (prod); ma la thi xep cuoi (5).
     */
    private static function bieuThucMuc()
    {
        $nhanh = '';
        foreach (array('01', '02', '03', '04', '05', '06', '07', '08', '09') as $ma) {
            $muc = Bh01DacTa::muc($ma);
            if ($muc !== null) {
                $nhanh .= " WHEN '" . $ma . "' THEN " . $muc;
            }
        }

        return 'CASE ma_loai_kcb' . $nhanh . ' ELSE 5 END';
    }
}
