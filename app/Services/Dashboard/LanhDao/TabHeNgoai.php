<?php

namespace App\Services\Dashboard\LanhDao;

use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tab Hệ ngoại: y lệnh phẫu thuật / đã thực hiện theo khoa ngoại (khoa điều trị cuối của hồ sơ, R13),
 * ca can thiệp tim mạch theo phòng.
 */
class TabHeNgoai
{
    use ExcludesDashboardPatientTypes;

    /** service_req_type_id của y lệnh phẫu thuật/thủ thuật. */
    const LOAI_Y_LENH_PT = 10;
    /** service_req_stt_id = đã hoàn thành. */
    const TRANG_THAI_XONG = 3;

    /** @var DanhMucDonVi */
    private $danhMuc;

    public function __construct(DanhMucDonVi $danhMuc)
    {
        $this->danhMuc = $danhMuc;
    }

    public function lay(KyLoc $ky): array
    {
        return Cache::remember($ky->khoaCache('he_ngoai'), self::phutCache($ky), function () use ($ky) {
            $du = $this->duLieuTho($ky);

            return self::dinhDang($du['pt'], $du['can_thiep'], $du['danh_muc'], $ky);
        });
    }

    /**
     * Số cho thẻ (Ruling R1): ['y_lenh_pt' => [hn, nb, 2cs], 'ca_pt' => [...], 'can_thiep_tm' => [...]].
     */
    public function tongCoSo(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'he_ngoai_tong'), self::phutCache($ky), function () use ($ky) {
            $du = $this->duLieuTho($ky);

            return self::tongHop($du['pt'], $du['can_thiep'], $du['danh_muc']);
        });
    }

    /** Dòng thô của mọi cơ sở + danh mục đơn vị (dùng chung cho lay và tongCoSo). */
    private function duLieuTho(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'he_ngoai_tho'), self::phutCache($ky), function () use ($ky) {
            return [
                'pt' => $this->truyVanPhauThuat($ky),
                'can_thiep' => $this->truyVanCanThiep($ky),
                'danh_muc' => $this->danhMuc->tai(substr($ky->to, 0, 8)),
            ];
        });
    }

    /**
     * Y lệnh phẫu thuật trong kỳ theo khoa điều trị cuối của hồ sơ và trạng thái. Cơ sở suy ra từ danh mục khoa ở bước định dạng.
     *
     * Ruling R13 (thay spec §5.4): gom theo his_treatment.last_department_id, KHÔNG theo sr.request_department_id,
     * vì trên HIS gần như mọi y lệnh phẫu thuật được chỉ định từ K26 (Gây mê/phòng mổ) nên khoa chỉ định không phân biệt được khoa ngoại.
     *
     * @return array dòng: last_department_id, service_req_stt_id, so_luong
     */
    protected function truyVanPhauThuat(KyLoc $ky): array
    {
        $loaiTru = $this->excludedPatientTypeIds();

        $sql = 'SELECT tr.last_department_id, sr.service_req_stt_id, COUNT(*) AS so_luong'
            . ' FROM his_service_req sr'
            . ' JOIN his_treatment tr ON tr.id = sr.treatment_id'
            . ' WHERE sr.service_req_type_id = ? AND sr.intruction_time BETWEEN ? AND ? AND sr.is_delete = 0 AND tr.is_delete = 0'
            . ($loaiTru ? ' AND (sr.tdl_patient_type_id IS NULL OR sr.tdl_patient_type_id NOT IN (' . self::dauHoi($loaiTru) . '))' : '')
            . ' GROUP BY tr.last_department_id, sr.service_req_stt_id';

        return self::chuanHoa(DB::connection('HISPro')->select(
            $sql,
            array_merge([self::LOAI_Y_LENH_PT, $ky->from, $ky->to], $loaiTru)
        ));
    }

    /**
     * Y lệnh đã hoàn thành tại phòng can thiệp tim mạch (một y lệnh = một ca), theo phòng.
     *
     * @return array dòng: execute_room_code, execute_room_name, branch_id, so_ca
     */
    protected function truyVanCanThiep(KyLoc $ky): array
    {
        $loaiTru = $this->excludedPatientTypeIds();
        $phong = self::tatCaPhongCanThiep();
        if (!$phong) {
            return [];
        }

        $sql = 'SELECT er.execute_room_code, er.execute_room_name, d.branch_id, COUNT(*) AS so_ca'
            . ' FROM his_service_req sr'
            . ' JOIN his_execute_room er ON er.room_id = sr.execute_room_id'
            . ' JOIN his_room ro ON ro.id = er.room_id'
            . ' JOIN his_department d ON d.id = ro.department_id'
            . ' WHERE er.execute_room_code IN (' . self::dauHoi($phong) . ')'
            . ' AND sr.service_req_stt_id = ? AND sr.intruction_time BETWEEN ? AND ? AND sr.is_delete = 0'
            . ($loaiTru ? ' AND (sr.tdl_patient_type_id IS NULL OR sr.tdl_patient_type_id NOT IN (' . self::dauHoi($loaiTru) . '))' : '')
            . ' GROUP BY er.execute_room_code, er.execute_room_name, d.branch_id';

        return self::chuanHoa(DB::connection('HISPro')->select(
            $sql,
            array_merge($phong, [self::TRANG_THAI_XONG, $ky->from, $ky->to], $loaiTru)
        ));
    }

    /**
     * @param array $pt dòng truyVanPhauThuat()
     * @param array $canThiep dòng truyVanCanThiep()
     * @param array $danhMuc kết quả DanhMucDonVi::tai()
     */
    public static function dinhDang(array $pt, array $canThiep, array $danhMuc, KyLoc $ky): array
    {
        $khoa = [];
        foreach (self::gomKhoaNgoai($pt, $danhMuc, self::coSoDangLoc($ky)) as $key => $d) {
            $khoa[] = [
                'nhan' => DanhMucDonVi::nhan($danhMuc['don_vi'][$key], $ky->coSo),
                'y_lenh' => $d['y_lenh'],
                'da_thuc_hien' => $d['da_thuc_hien'],
            ];
        }
        usort($khoa, function ($a, $b) {
            return ($b['y_lenh'] - $a['y_lenh']) ?: strcmp($a['nhan'], $b['nhan']);
        });

        $phong = [];
        foreach (self::gomCanThiep($canThiep, self::coSoDangLoc($ky)) as $d) {
            $phong[] = [
                'ma' => $d['ma'],
                'nhan' => DanhMucDonVi::nhan(['ten' => $d['ten'], 'branch_id' => $d['branch_id']], $ky->coSo),
                'so_ca' => $d['so_ca'],
            ];
        }
        usort($phong, function ($a, $b) {
            return strcmp($a['ma'], $b['ma']);
        });

        return [
            'theo_khoa' => [
                'categories' => array_column($khoa, 'nhan'),
                'y_lenh' => array_column($khoa, 'y_lenh'),
                'da_thuc_hien' => array_column($khoa, 'da_thuc_hien'),
            ],
            'can_thiep' => [
                'categories' => array_column($phong, 'nhan'),
                'so_ca' => array_column($phong, 'so_ca'),
            ],
        ];
    }

    /**
     * Số thẻ [hn, nb, 2cs] từ dòng thô; 2cs = hn + nb (cộng dồn đếm).
     */
    public static function tongHop(array $pt, array $canThiep, array $danhMuc): array
    {
        $kq = ['y_lenh_pt' => [], 'ca_pt' => [], 'can_thiep_tm' => []];
        foreach (['hn', 'nb'] as $k) {
            $yLenh = 0;
            $daLam = 0;
            foreach (self::gomKhoaNgoai($pt, $danhMuc, [$k]) as $d) {
                $yLenh += $d['y_lenh'];
                $daLam += $d['da_thuc_hien'];
            }
            $ca = 0;
            foreach (self::gomCanThiep($canThiep, [$k]) as $d) {
                $ca += $d['so_ca'];
            }
            $kq['y_lenh_pt'][$k] = $yLenh;
            $kq['ca_pt'][$k] = $daLam;
            $kq['can_thiep_tm'][$k] = $ca;
        }
        foreach ($kq as $ten => $v) {
            $kq[$ten]['2cs'] = $v['hn'] + $v['nb'];
        }

        return $kq;
    }

    /**
     * Gom y lệnh theo đơn vị (branch|mã) của khoa ngoại thuộc các cơ sở $coSo.
     *
     * @param string[] $coSo mã cơ sở (hn|nb)
     * @return array [khoaDonVi => ['y_lenh' => int, 'da_thuc_hien' => int]]
     */
    private static function gomKhoaNgoai(array $pt, array $danhMuc, array $coSo): array
    {
        $duocPhep = [];
        foreach ($coSo as $k) {
            $branch = (int) config("dashboard_lanh_dao.co_so.{$k}.branch_id");
            foreach ((array) config("dashboard_lanh_dao.khoa_ngoai.{$k}", []) as $ma) {
                $duocPhep[$branch . '|' . $ma] = true;
            }
        }

        $kq = [];
        foreach ($pt as $d) {
            $d = (object) (array) $d;
            $key = isset($danhMuc['khoa'][$d->last_department_id]) ? $danhMuc['khoa'][$d->last_department_id] : null;
            if ($key === null || !isset($duocPhep[$key]) || !isset($danhMuc['don_vi'][$key])) {
                continue;
            }
            if (!isset($kq[$key])) {
                $kq[$key] = ['y_lenh' => 0, 'da_thuc_hien' => 0];
            }
            $kq[$key]['y_lenh'] += (int) $d->so_luong;
            if ((int) $d->service_req_stt_id === self::TRANG_THAI_XONG) {
                $kq[$key]['da_thuc_hien'] += (int) $d->so_luong;
            }
        }

        return $kq;
    }

    /**
     * Phòng can thiệp thuộc cấu hình và đúng cơ sở.
     *
     * @return array [branch|mã phòng => ['ma', 'ten', 'branch_id', 'so_ca']]
     */
    private static function gomCanThiep(array $dong, array $coSo): array
    {
        $branchDuocPhep = [];
        foreach ($coSo as $k) {
            $branchDuocPhep[(int) config("dashboard_lanh_dao.co_so.{$k}.branch_id")] = true;
        }
        $maDuocPhep = array_flip(self::tatCaPhongCanThiep());

        $kq = [];
        foreach ($dong as $d) {
            $d = (object) (array) $d;
            $branch = (int) $d->branch_id;
            if (!isset($branchDuocPhep[$branch]) || !isset($maDuocPhep[$d->execute_room_code])) {
                continue;
            }
            $key = $branch . '|' . $d->execute_room_code;
            if (!isset($kq[$key])) {
                $kq[$key] = ['ma' => $d->execute_room_code, 'ten' => (string) $d->execute_room_name, 'branch_id' => $branch, 'so_ca' => 0];
            }
            $kq[$key]['so_ca'] += (int) $d->so_ca;
        }

        return $kq;
    }

    /** @return string[] hn|nb theo cơ sở đang lọc */
    private static function coSoDangLoc(KyLoc $ky): array
    {
        return $ky->coSo === '2cs' ? ['hn', 'nb'] : [$ky->coSo];
    }

    /** @return string[] */
    private static function tatCaPhongCanThiep(): array
    {
        return array_values(array_map('strval', (array) config('dashboard_lanh_dao.phong_can_thiep_tim_mach', [])));
    }

    private static function dauHoi(array $ds): string
    {
        return implode(',', array_fill(0, count($ds), '?'));
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
}
