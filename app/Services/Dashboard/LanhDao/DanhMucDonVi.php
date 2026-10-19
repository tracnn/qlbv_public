<?php

namespace App\Services\Dashboard\LanhDao;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Danh muc don vi cho Dashboard lanh dao: gom khoa HIS (HIS_DEPARTMENT) theo bhyt_code
 * thanh "don vi giuong phe duyet" (department_bed_catalogs). Khoa don vi = "{branch_id}|{ma}".
 */
class DanhMucDonVi
{
    /**
     * Ham thuan.
     *
     * @return array ['khoa' => [department_id => khoaDonVi], 'don_vi' => [khoaDonVi => [ma, ten, branch_id, giuong_pd]]]
     */
    public static function dung(array $khoaHis, array $dongGiuong, string $ngayYmd): array
    {
        $cskcbTheoBranch = [];
        foreach (config('dashboard_lanh_dao.co_so', []) as $coSo) {
            $cskcbTheoBranch[(int) $coSo['branch_id']] = (string) $coSo['ma_cskcb'];
        }
        $boQua = config('dashboard_lanh_dao.ma_khoa_bo_qua', []);

        $nhom = [];
        foreach ($khoaHis as $khoa) {
            $ma = trim((string) $khoa->bhyt_code);
            $branch = (int) $khoa->branch_id;
            if ($ma === '' || in_array($ma, $boQua, true) || !isset($cskcbTheoBranch[$branch])) {
                continue;
            }
            $key = $branch . '|' . $ma;
            $nhom[$key]['ma'] = $ma;
            $nhom[$key]['branch_id'] = $branch;
            $nhom[$key]['ten_his'][] = trim((string) $khoa->department_name);
            $nhom[$key]['ids'][] = $khoa->id;
        }

        $khoaMap = [];
        $donVi = [];
        foreach ($nhom as $key => $n) {
            $dong = self::chonDongGiuong($dongGiuong, $n['ma'], $cskcbTheoBranch[$n['branch_id']], $ngayYmd);

            $tenHis = $n['ten_his'];
            usort($tenHis, function ($a, $b) {
                return (mb_strlen($a) - mb_strlen($b)) ?: strcmp($a, $b);
            });

            $ten = ($dong !== null && trim((string) $dong->ten_khoa) !== '') ? $dong->ten_khoa : $tenHis[0];
            $giuong = ($dong !== null && $dong->giuong_pd !== null && $dong->giuong_pd !== '')
                ? (int) $dong->giuong_pd : null;

            $donVi[$key] = [
                'ma' => $n['ma'],
                'ten' => $ten,
                'branch_id' => $n['branch_id'],
                'giuong_pd' => $giuong,
            ];
            foreach ($n['ids'] as $id) {
                $khoaMap[$id] = $key;
            }
        }

        return ['khoa' => $khoaMap, 'don_vi' => $donVi];
    }

    /**
     * Nhan hien thi: 2cs them hau to co so; con lai giu nguyen ten.
     */
    public static function nhan(array $donVi, string $coSo): string
    {
        if ($coSo !== '2cs') {
            return $donVi['ten'];
        }
        foreach (config('dashboard_lanh_dao.co_so', []) as $cs) {
            if ((int) $cs['branch_id'] === (int) $donVi['branch_id']) {
                return $donVi['ten'] . ' (' . $cs['nhan'] . ')';
            }
        }

        return $donVi['ten'];
    }

    /**
     * Doc HIS + danh muc giuong (chi doc), cache 60 phut.
     */
    public static function tai(string $ngayYmd): array
    {
        return Cache::remember('lanh_dao:don_vi:' . $ngayYmd, 60, function () use ($ngayYmd) {
            $khoaHis = DB::connection('HISPro')->table('his_department')
                ->where('is_active', 1)
                ->get(['id', 'department_name', 'bhyt_code', 'branch_id'])
                ->all();
            $dongGiuong = DB::table('department_bed_catalogs')->get()->all();

            return self::dung($khoaHis, $dongGiuong, $ngayYmd);
        });
    }

    private static function chonDongGiuong(array $dongGiuong, string $ma, string $maCskcb, string $ngayYmd)
    {
        $chon = null;
        foreach ($dongGiuong as $d) {
            if ((string) $d->ma_khoa !== $ma || (string) $d->ma_cskcb !== $maCskcb) {
                continue;
            }
            $tu = trim((string) $d->tu_ngay);
            $den = trim((string) $d->den_ngay);
            if (($tu !== '' && $tu > $ngayYmd) || ($den !== '' && $den < $ngayYmd)) {
                continue;
            }
            if ($chon === null || $tu > trim((string) $chon->tu_ngay)) {
                $chon = $d;
            }
        }

        return $chon;
    }
}
