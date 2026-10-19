<?php

namespace App\Services\Dashboard\LanhDao;

use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;
use App\Services\Dashboard\ChiSoDungChung;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tab Khám & thuốc: trung vị tổng thời gian khám theo giờ tiếp đón, phân bố kê đơn → mua thuốc,
 * khám mới / cũ theo cơ sở.
 */
class TabKhamThuoc
{
    use ExcludesDashboardPatientTypes;

    /** exp_mest_type_code của phiếu xuất loại "Bán" (id 8 trên HIS). */
    const MA_LOAI_BAN = '08';

    /** Khung giờ tiếp đón hiển thị ở biểu đồ. */
    const GIO_TU = 6;
    const GIO_DEN = 17;

    public function lay(KyLoc $ky): array
    {
        return Cache::remember($ky->khoaCache('kham_thuoc'), self::phutCache($ky), function () use ($ky) {
            return self::dinhDang($this->thoiGianKham($ky), $this->keDonMuaThuoc($ky), $this->moiCu($ky), $ky);
        });
    }

    /**
     * Số cho thẻ (Ruling R1): mỗi chỉ số là [hn, nb, 2cs]; 2cs lấy từ dòng gộp của SQL (trung vị trên dữ liệu gộp).
     * Giờ tiếp đón ngoài 6–17 vẫn nằm trong các số này.
     */
    public function tongCoSo(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'kham_thuoc_tong'), self::phutCache($ky), function () use ($ky) {
            return self::tongHop($this->thoiGianKham($ky), $this->keDonMuaThuoc($ky), $this->moiCu($ky));
        });
    }

    /** Dòng thời gian khám của mọi cơ sở (dùng chung cho lay và tongCoSo). */
    private function thoiGianKham(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'tg_kham'), self::phutCache($ky), function () use ($ky) {
            return $this->truyVanThoiGianKham($ky);
        });
    }

    private function keDonMuaThuoc(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'ke_don_mua'), self::phutCache($ky), function () use ($ky) {
            return $this->truyVanKeDonMuaThuoc($ky);
        });
    }

    private function moiCu(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'moi_cu'), self::phutCache($ky), function () use ($ky) {
            return $this->truyVanMoiCu($ky);
        });
    }

    /**
     * Hồ sơ khám (loại 1) ra viện trong kỳ, mọi cơ sở cấu hình. Phút tính ở truy vấn trong, gom nhóm ở truy vấn ngoài
     * (binding không đứng trong GROUP BY). gb/gg = GROUPING() để nhận dòng tổng, không dựa vào NULL.
     *
     * @return array dòng: branch_id, gio, trung_vi, so_ho_so, gb, gg
     */
    protected function truyVanThoiGianKham(KyLoc $ky): array
    {
        $loaiTru = $this->excludedPatientTypeIds();
        $branch = self::tatCaBranch();
        $in = function (array $ds) {
            return implode(',', array_fill(0, count($ds), '?'));
        };

        $sql = 'SELECT x.branch_id, x.gio, MEDIAN(x.phut) AS trung_vi, COUNT(*) AS so_ho_so,'
            . ' GROUPING(x.branch_id) AS gb, GROUPING(x.gio) AS gg FROM ('
            . ' SELECT tr.branch_id AS branch_id, TO_NUMBER(SUBSTR(tr.in_time, 9, 2)) AS gio,'
            . " (TO_DATE(tr.out_time, 'YYYYMMDDHH24MISS') - TO_DATE(tr.in_time, 'YYYYMMDDHH24MISS')) * 1440 AS phut"
            . ' FROM his_treatment tr'
            . ' WHERE tr.tdl_treatment_type_id = 1 AND tr.out_time BETWEEN ? AND ? AND tr.in_time IS NOT NULL'
            . ' AND tr.is_delete = 0 AND tr.branch_id IN (' . $in($branch) . ')'
            . ($loaiTru ? ' AND (tr.tdl_patient_type_id IS NULL OR tr.tdl_patient_type_id NOT IN (' . $in($loaiTru) . '))' : '')
            . ') x WHERE x.phut > 0 AND x.phut <= 1440'
            . ' GROUP BY GROUPING SETS ((x.branch_id, x.gio), (x.branch_id), (x.gio), ())';

        return self::chuanHoa(DB::connection('HISPro')->select($sql, array_merge([$ky->from, $ky->to], $branch, $loaiTru)));
    }

    /**
     * Phiếu xuất "Bán" hoàn tất trong kỳ nối được đơn kê; cơ sở = his_treatment.branch_id.
     *
     * @return array dòng: branch_id, nhom (chỉ số theo NHOM_PHUT), trung_vi, so_phieu, gb, gg
     */
    protected function truyVanKeDonMuaThuoc(KyLoc $ky): array
    {
        $loaiTru = $this->excludedPatientTypeIds();
        $branch = self::tatCaBranch();
        $in = function (array $ds) {
            return implode(',', array_fill(0, count($ds), '?'));
        };

        $when = '';
        foreach (array_values(TinhToan::NHOM_PHUT) as $i => $khoang) {
            $dieuKien = 'x.phut >= ' . (int) $khoang[0] . ($khoang[1] === null ? '' : ' AND x.phut < ' . (int) $khoang[1]);
            $when .= ' WHEN ' . $dieuKien . ' THEN ' . $i;
        }

        $sql = 'SELECT y.branch_id, y.nhom, MEDIAN(y.phut) AS trung_vi, COUNT(*) AS so_phieu,'
            . ' GROUPING(y.branch_id) AS gb, GROUPING(y.nhom) AS gg FROM ('
            . ' SELECT x.branch_id, x.phut, CASE' . $when . ' END AS nhom FROM ('
            . ' SELECT tr.branch_id AS branch_id,'
            . " (TO_DATE(e.finish_time, 'YYYYMMDDHH24MISS') - TO_DATE(sr.intruction_time, 'YYYYMMDDHH24MISS')) * 1440 AS phut"
            . ' FROM his_exp_mest e'
            . ' JOIN his_exp_mest_type ty ON ty.id = e.exp_mest_type_id'
            . ' JOIN his_service_req sr ON sr.id = e.prescription_id'
            . ' JOIN his_treatment tr ON tr.id = sr.treatment_id'
            . ' WHERE ty.exp_mest_type_code = ? AND e.finish_time BETWEEN ? AND ?'
            . ' AND tr.branch_id IN (' . $in($branch) . ')'
            . ($loaiTru ? ' AND (tr.tdl_patient_type_id IS NULL OR tr.tdl_patient_type_id NOT IN (' . $in($loaiTru) . '))' : '')
            . ') x WHERE x.phut >= 0) y'
            . ' GROUP BY GROUPING SETS ((y.branch_id, y.nhom), (y.branch_id), (y.nhom), ())';

        return self::chuanHoa(DB::connection('HISPro')->select($sql, array_merge([self::MA_LOAI_BAN, $ky->from, $ky->to], $branch, $loaiTru)));
    }

    /**
     * Khám mới / cũ (công thức trang chủ) theo his_treatment.branch_id.
     *
     * @return array ['moi' => [branch_id => số], 'cu' => [branch_id => số]]
     */
    protected function truyVanMoiCu(KyLoc $ky): array
    {
        $loaiTru = $this->excludedPatientTypeIds();
        $branch = self::tatCaBranch();
        $dem = function ($q) {
            $kq = [];
            $dong = $q->selectRaw('his_treatment.branch_id AS branch_id, COUNT(*) AS so_luong')
                ->groupBy('his_treatment.branch_id')->get();
            foreach ($dong as $d) {
                $d = array_change_key_case((array) $d, CASE_LOWER);
                $kq[(int) $d['branch_id']] = (int) $d['so_luong'];
            }

            return $kq;
        };

        return [
            'moi' => $dem(ChiSoDungChung::benhNhanMoi($ky->from, $ky->to, $loaiTru, $branch)),
            'cu' => $dem(ChiSoDungChung::taiKham($ky->from, $ky->to, $loaiTru, $branch)),
        ];
    }

    /**
     * @param array $kham dòng truyVanThoiGianKham()
     * @param array $thuoc dòng truyVanKeDonMuaThuoc()
     * @param array $moiCu kết quả truyVanMoiCu()
     */
    public static function dinhDang(array $kham, array $thuoc, array $moiCu, KyLoc $ky): array
    {
        $branch = $ky->coSo === '2cs' ? null : $ky->branchIds()[0];

        $theoGio = self::chonDong($kham, 'gio', $branch);
        $gio = range(self::GIO_TU, self::GIO_DEN);
        $trungVi = [];
        $soHoSo = [];
        foreach ($gio as $g) {
            $trungVi[] = isset($theoGio[$g]) && $theoGio[$g]->trung_vi !== null ? round((float) $theoGio[$g]->trung_vi, 1) : null;
            $soHoSo[] = isset($theoGio[$g]) ? (int) $theoGio[$g]->so_ho_so : 0;
        }

        $theoNhom = self::chonDong($thuoc, 'nhom', $branch);
        $soPhieu = [];
        foreach (array_keys(array_values(TinhToan::NHOM_PHUT)) as $i) {
            $soPhieu[] = isset($theoNhom[$i]) ? (int) $theoNhom[$i]->so_phieu : 0;
        }

        $coSo = [];
        $moi = [];
        $cu = [];
        foreach (['hn', 'nb'] as $k) {
            if ($ky->coSo !== '2cs' && $ky->coSo !== $k) {
                continue;
            }
            $id = (int) config("dashboard_lanh_dao.co_so.{$k}.branch_id");
            $coSo[] = config("dashboard_lanh_dao.co_so.{$k}.nhan");
            $moi[] = isset($moiCu['moi'][$id]) ? (int) $moiCu['moi'][$id] : 0;
            $cu[] = isset($moiCu['cu'][$id]) ? (int) $moiCu['cu'][$id] : 0;
        }

        return [
            'tg_kham_theo_gio' => ['gio' => $gio, 'trung_vi' => $trungVi, 'so_ho_so' => $soHoSo],
            'ke_don_mua_thuoc' => ['nhom' => array_keys(TinhToan::NHOM_PHUT), 'so_phieu' => $soPhieu],
            'moi_cu' => ['co_so' => $coSo, 'moi' => $moi, 'cu' => $cu],
        ];
    }

    /**
     * Số thẻ [hn, nb, 2cs] từ dòng thô: hn/nb = dòng tổng của cơ sở, 2cs = dòng tổng toàn bộ (GROUPING SETS).
     */
    public static function tongHop(array $kham, array $thuoc, array $moiCu): array
    {
        $id = function ($k) {
            return (int) config("dashboard_lanh_dao.co_so.{$k}.branch_id");
        };
        $tong = function (array $dong, $cotNhom, $cotSo) use ($id) {
            $kq = ['hn' => [null, 0], 'nb' => [null, 0], '2cs' => [null, 0]];
            $coSoTheoId = [$id('hn') => 'hn', $id('nb') => 'nb'];
            foreach ($dong as $d) {
                $d = (object) (array) $d;
                if (!self::laDongGop($d, 'gg', $cotNhom)) {
                    continue;
                }
                if (self::laDongGop($d, 'gb', 'branch_id')) {
                    $khoa = '2cs';
                } elseif (isset($coSoTheoId[(int) $d->branch_id])) {
                    $khoa = $coSoTheoId[(int) $d->branch_id];
                } else {
                    continue;
                }
                if ($d->trung_vi === null) {
                    continue;
                }
                $kq[$khoa] = [round((float) $d->trung_vi, 1), (int) $d->{$cotSo}];
            }

            return $kq;
        };

        $k = $tong($kham, 'gio', 'so_ho_so');
        $t = $tong($thuoc, 'nhom', 'so_phieu');
        $cot = function (array $ds, $i) {
            return ['hn' => $ds['hn'][$i], 'nb' => $ds['nb'][$i], '2cs' => $ds['2cs'][$i]];
        };
        $dem = function ($loai) use ($id, $moiCu) {
            $hn = isset($moiCu[$loai][$id('hn')]) ? (int) $moiCu[$loai][$id('hn')] : 0;
            $nb = isset($moiCu[$loai][$id('nb')]) ? (int) $moiCu[$loai][$id('nb')] : 0;

            return ['hn' => $hn, 'nb' => $nb, '2cs' => $hn + $nb];
        };

        return [
            'tg_kham_phut' => $cot($k, 0),
            'tg_kham_so_ho_so' => $cot($k, 1),
            'ke_don_phut' => $cot($t, 0),
            'ke_don_so_phieu' => $cot($t, 1),
            'moi' => $dem('moi'),
            'cu' => $dem('cu'),
        ];
    }

    /**
     * Dòng chi tiết theo $cotNhom (giờ/nhóm) của một cơ sở ($branch) hoặc dòng gộp 2 cơ sở ($branch = null).
     *
     * @return object[] [giá trị cột nhóm => dòng]
     */
    private static function chonDong(array $dong, $cotNhom, $branch): array
    {
        $kq = [];
        foreach ($dong as $d) {
            $d = (object) (array) $d;
            if (self::laDongGop($d, 'gg', $cotNhom)) {
                continue;
            }
            if (self::laDongGop($d, 'gb', 'branch_id') !== ($branch === null)) {
                continue;
            }
            if ($branch !== null && (int) $d->branch_id !== $branch) {
                continue;
            }
            $kq[(int) $d->{$cotNhom}] = $d;
        }

        return $kq;
    }

    /** Dòng này có gộp theo cột không? Dùng cờ GROUPING() ($co) nếu có, không thì suy từ NULL của $cot. */
    private static function laDongGop($d, $co, $cot): bool
    {
        if (isset($d->{$co})) {
            return (int) $d->{$co} === 1;
        }

        return !isset($d->{$cot});
    }

    private static function chuanHoa(array $dong): array
    {
        return array_map(function ($d) {
            return (object) array_change_key_case((array) $d, CASE_LOWER);
        }, $dong);
    }

    private static function phutCache(KyLoc $ky): int
    {
        return $ky->daKetThuc() ? 60 : 5;
    }

    /** Khoá cache không phụ thuộc lựa chọn cơ sở (dữ liệu thô chứa mọi cơ sở). */
    private static function khoaChung(KyLoc $ky, string $phan): string
    {
        return "lanh_dao:{$phan}:{$ky->from}:{$ky->to}";
    }

    /** @return int[] */
    private static function tatCaBranch(): array
    {
        return array_values(array_map(function ($cs) {
            return (int) $cs['branch_id'];
        }, config('dashboard_lanh_dao.co_so', [])));
    }
}
