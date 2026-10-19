<?php

namespace App\Services\Dashboard\LanhDao;

use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tab Thanh toán: tỷ lệ thanh toán QR và tỷ lệ hoàn ứng online theo đơn vị giường phê duyệt.
 */
class TabThanhToan
{
    use ExcludesDashboardPatientTypes;

    /** @var DanhMucDonVi */
    private $danhMuc;

    public function __construct(DanhMucDonVi $danhMuc)
    {
        $this->danhMuc = $danhMuc;
    }

    public function lay(KyLoc $ky): array
    {
        $phut = $ky->daKetThuc() ? 60 : 5;

        return Cache::remember($ky->khoaCache('thanh_toan'), $phut, function () use ($ky) {
            $danhMuc = $this->danhMuc->tai(substr($ky->to, 0, 8));

            $kq = self::dinhDang($this->truyVan($ky), $danhMuc, $ky);
            if ($kq['bo_qua']['khong_ho_so'] || $kq['bo_qua']['ngoai_danh_muc']) {
                Log::debug('Dashboard lanh dao - tab thanh toan: giao dich bi bo', $kq['bo_qua'] + ['from' => $ky->from, 'to' => $ky->to]);
            }

            return $kq;
        });
    }

    /**
     * Một truy vấn HIS (chỉ đọc). Lọc branch theo khoa làm ở dinhDang.
     *
     * @return array dòng: last_department_id, nhom (thu|hoan), la_online (0|1), so_gd, tien
     */
    protected function truyVan(KyLoc $ky): array
    {
        $qr = array_values((array) config('dashboard_lanh_dao.pay_form_qr'));
        $hoan = array_values((array) config('dashboard_lanh_dao.pay_form_hoan_online'));
        $loaiTru = $this->excludedPatientTypeIds();

        $in = function (array $ds) {
            return implode(',', array_fill(0, count($ds), '?'));
        };
        // IN () rỗng không hợp lệ trong Oracle: mã không tồn tại thì không khớp gì
        $qr = $qr ?: [''];
        $hoan = $hoan ?: [''];

        $sql = 'SELECT x.khoa_id AS last_department_id, x.nhom, x.la_online, COUNT(*) AS so_gd, SUM(x.amount) AS tien FROM ('
            . ' SELECT tr.last_department_id AS khoa_id,'
            . " CASE WHEN t.transaction_type_id = 2 THEN 'hoan' ELSE 'thu' END AS nhom,"
            . ' CASE WHEN t.transaction_type_id = 2'
            . ' THEN (CASE WHEN pf.pay_form_code IN (' . $in($hoan) . ') THEN 1 ELSE 0 END)'
            . ' ELSE (CASE WHEN pf.pay_form_code IN (' . $in($qr) . ') THEN 1 ELSE 0 END) END AS la_online,'
            . ' t.amount AS amount'
            . ' FROM his_transaction t'
            . ' LEFT JOIN his_treatment tr ON tr.id = t.treatment_id'
            . ' LEFT JOIN his_pay_form pf ON pf.id = t.pay_form_id'
            . ' WHERE t.transaction_time BETWEEN ? AND ?'
            . ' AND t.is_delete = 0 AND t.is_cancel IS NULL'
            . ' AND t.transaction_type_id IN (1, 2, 3)'
            . ($loaiTru ? ' AND (tr.tdl_patient_type_id IS NULL OR tr.tdl_patient_type_id NOT IN (' . $in($loaiTru) . '))' : '')
            . ') x GROUP BY x.khoa_id, x.nhom, x.la_online';

        $bindings = array_merge($hoan, $qr, [$ky->from, $ky->to], $loaiTru);

        return array_map(function ($d) {
            return (object) array_change_key_case((array) $d, CASE_LOWER);
        }, DB::connection('HISPro')->select($sql, $bindings));
    }

    /**
     * @param array $dong dòng của truyVan()
     * @param array $danhMuc kết quả DanhMucDonVi::tai()
     * @return array ['qr' => Chuoi, 'hoan_online' => Chuoi, 'bo_qua' => ['khong_ho_so' => int, 'ngoai_danh_muc' => int]]
     */
    public static function dinhDang(array $dong, array $danhMuc, KyLoc $ky): array
    {
        $branch = $ky->branchIds();
        $gop = ['thu' => [], 'hoan' => []];
        $boQua = ['khong_ho_so' => 0, 'ngoai_danh_muc' => 0];

        foreach ($dong as $d) {
            $d = (object) (array) $d;
            if (!isset($gop[$d->nhom])) {
                continue;
            }
            if ($d->last_department_id === null || $d->last_department_id === '') {
                $boQua['khong_ho_so'] += (int) $d->so_gd;
                continue;
            }
            $khoa = (int) $d->last_department_id;
            if (!isset($danhMuc['khoa'][$khoa])) {
                $boQua['ngoai_danh_muc'] += (int) $d->so_gd;
                continue;
            }
            $dv = $danhMuc['khoa'][$khoa];
            if (!isset($danhMuc['don_vi'][$dv]) || !in_array((int) $danhMuc['don_vi'][$dv]['branch_id'], $branch, true)) {
                continue;
            }
            if (!isset($gop[$d->nhom][$dv])) {
                $gop[$d->nhom][$dv] = ['so_gd' => 0, 'tong_gd' => 0, 'tien' => 0.0, 'tong_tien' => 0.0];
            }
            $o = &$gop[$d->nhom][$dv];
            $o['tong_gd'] += (int) $d->so_gd;
            $o['tong_tien'] += (float) $d->tien;
            if ((int) $d->la_online === 1) {
                $o['so_gd'] += (int) $d->so_gd;
                $o['tien'] += (float) $d->tien;
            }
            unset($o);
        }

        return [
            'qr' => self::chuoi($gop['thu'], $danhMuc, $ky),
            'hoan_online' => self::chuoi($gop['hoan'], $danhMuc, $ky),
            'bo_qua' => $boQua,
        ];
    }

    private static function chuoi(array $theoDonVi, array $danhMuc, KyLoc $ky): array
    {
        $cot = [];
        foreach ($theoDonVi as $dv => $o) {
            if ($o['tong_gd'] <= 0) {
                continue;
            }
            $cot[] = [
                'ten' => DanhMucDonVi::nhan($danhMuc['don_vi'][$dv], $ky->coSo),
                'ty_le' => TinhToan::tyLe($o['so_gd'], $o['tong_gd']),
                'so_gd' => $o['so_gd'],
                'tong_gd' => $o['tong_gd'],
                'tien' => $o['tien'],
                'tong_tien' => $o['tong_tien'],
            ];
        }

        $cot = TinhToan::sapGiam($cot, 'ty_le');

        return [
            'categories' => array_column($cot, 'ten'),
            'ty_le' => array_column($cot, 'ty_le'),
            'so_gd' => array_column($cot, 'so_gd'),
            'tong_gd' => array_column($cot, 'tong_gd'),
            'tien' => array_column($cot, 'tien'),
            'tong_tien' => array_column($cot, 'tong_tien'),
        ];
    }
}
