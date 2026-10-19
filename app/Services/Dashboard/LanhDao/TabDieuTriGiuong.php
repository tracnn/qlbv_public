<?php

namespace App\Services\Dashboard\LanhDao;

use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;
use App\Services\Dashboard\ChiSoDungChung;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tab Điều trị & giường: ngày điều trị TB theo đơn vị, công suất giường theo thời lượng nằm
 * (mẫu = giường phê duyệt), bảng giường hiện tại.
 */
class TabDieuTriGiuong
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
        $kq = Cache::remember($ky->khoaCache('dieu_tri_giuong'), self::phutCache($ky), function () use ($ky) {
            $danhMuc = $this->danhMuc->tai(substr($ky->to, 0, 8));
            $ng = $this->ngayGiuong($ky, $danhMuc);

            $kq = self::dinhDang($this->ngayDieuTri($ky), $ng['ngay_giuong'], [], $danhMuc, $ky, $ng['den']);
            unset($kq['giuong_hien_tai']);

            return $kq;
        });

        // Ảnh chụp lúc tải, không theo bộ lọc ngày (spec §5.2): ngoài cache theo kỳ
        $kq['giuong_hien_tai'] = self::giuongHienTai(
            $this->dangNam(),
            $this->danhMuc->tai(now()->format('Ymd')),
            $ky->coSo,
            $ky->branchIds()
        );

        return $kq;
    }

    /**
     * Số cho thẻ (Ruling R1): ['ngay_dieu_tri' => [hn, nb, 2cs], 'cong_suat_giuong' => [hn, nb, 2cs]].
     */
    public function tongCoSo(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'dieu_tri_giuong_tong'), self::phutCache($ky), function () use ($ky) {
            $danhMuc = $this->danhMuc->tai(substr($ky->to, 0, 8));
            $ng = $this->ngayGiuong($ky, $danhMuc);

            return self::tongHop($this->ngayDieuTri($ky), $ng['ngay_giuong'], $danhMuc, $ky, $ng['den']);
        });
    }

    /** Dòng ngày điều trị của mọi cơ sở (dùng chung cho lay và tongCoSo, không phụ thuộc lựa chọn cơ sở). */
    private function ngayDieuTri(KyLoc $ky): array
    {
        return Cache::remember(self::khoaChung($ky, 'ngay_dieu_tri'), self::phutCache($ky), function () use ($ky) {
            return $this->truyVanNgayDieuTri($ky);
        });
    }

    /**
     * Ngày-giường của mọi cơ sở, cache KÈM mốc cắt: mẫu số công suất phải tính tới đúng mốc này,
     * không phải "now" của request sau (kỳ chứa hôm nay).
     *
     * @return array ['den' => YmdHis, 'ngay_giuong' => [khoaDonVi => ngày-giường]]
     */
    private function ngayGiuong(KyLoc $ky, array $danhMuc): array
    {
        return Cache::remember(self::khoaChung($ky, 'ngay_giuong'), self::phutCache($ky), function () use ($ky, $danhMuc) {
            $batDau = microtime(true);
            $den = $ky->denThucTe();
            $kq = self::ngayGiuongTheoDonVi(
                $this->truyVanLuotNam($ky),
                $danhMuc['khoa'],
                self::epoch($ky->from),
                self::epoch($den)
            );
            Log::debug('Dashboard lanh dao - ngay giuong', [
                'from' => $ky->from, 'den' => $den,
                'giay' => round(microtime(true) - $batDau, 2),
                'ram_dinh_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);

            return ['den' => $den, 'ngay_giuong' => $kq];
        });
    }

    /** Số đang nằm theo khoa HIS, mọi cơ sở; cache 5 phút, không phụ thuộc kỳ. */
    private function dangNam(): array
    {
        return Cache::remember('lanh_dao:dang_nam', 5, function () {
            return $this->truyVanDangNam();
        });
    }

    /**
     * Hồ sơ nội trú ra viện trong kỳ (công thức trang chủ), mọi cơ sở cấu hình.
     * so_ho_so đếm hồ sơ có số ngày khác NULL — khớp trang chủ (bỏ dòng day_count NULL).
     *
     * @return array dòng: last_department_id, branch_id, tong_ngay, so_ho_so
     */
    protected function truyVanNgayDieuTri(KyLoc $ky): array
    {
        $bieuThuc = ChiSoDungChung::SQL_SO_NGAY_DIEU_TRI;

        $dong = ChiSoDungChung::noiTruRaVien($ky->from, $ky->to, $this->excludedPatientTypeIds(), self::tatCaBranch())
            ->selectRaw("last_department_id, branch_id, SUM({$bieuThuc}) AS tong_ngay, COUNT({$bieuThuc}) AS so_ho_so")
            ->groupBy('last_department_id', 'branch_id')
            ->get()
            ->all();

        return array_map(function ($d) {
            return (object) array_change_key_case((array) $d, CASE_LOWER);
        }, $dong);
    }

    /**
     * Lượt nằm giường chồng lên kỳ, sắp theo treatment_id, department_id, add_time.
     * Trả cursor để không nạp hết vào RAM.
     *
     * @return iterable dòng: treatment_id, department_id, add_time, remove_time, out_time
     */
    protected function truyVanLuotNam(KyLoc $ky): iterable
    {
        $q = DB::connection('HISPro')
            ->table('his_treatment_bed_room as tbr')
            ->join('his_bed_room as br', 'br.id', '=', 'tbr.bed_room_id')
            ->join('his_room as r', 'r.id', '=', 'br.room_id')
            ->join('his_treatment as t', 't.id', '=', 'tbr.treatment_id')
            ->leftJoin('his_co_treatment as ct', 'ct.id', '=', 'tbr.co_treatment_id')
            ->whereNull('ct.id')
            ->where('tbr.is_delete', 0)
            ->whereIn('t.tdl_treatment_type_id', [3, 4])
            ->where('tbr.add_time', '<=', $ky->denThucTe())
            ->where(function ($w) use ($ky) {
                $w->whereNull('tbr.remove_time')->orWhere('tbr.remove_time', '>=', $ky->from);
            });

        $loaiTru = $this->excludedPatientTypeIds();
        if ($loaiTru) {
            $q->whereNotIn('t.tdl_patient_type_id', $loaiTru);
        }

        return $q->select('tbr.treatment_id', 'r.department_id', 'tbr.add_time', 'tbr.remove_time', 't.out_time')
            ->orderBy('tbr.treatment_id')
            ->orderBy('r.department_id')
            ->orderBy('tbr.add_time')
            ->cursor();
    }

    /**
     * Số BN đang nằm theo khoa HIS — đúng điều kiện HomeController::bedStatusByDepartment.
     *
     * @return array dòng: department_id, dang_nam
     */
    protected function truyVanDangNam(): array
    {
        $loaiTru = $this->excludedPatientTypeIds();
        $sql = 'SELECT r.department_id, COUNT(*) AS dang_nam'
            . ' FROM his_treatment_bed_room tbr'
            . ' JOIN his_bed_room br ON br.id = tbr.bed_room_id'
            . ' JOIN his_room r ON r.id = br.room_id'
            . ' JOIN his_treatment t ON t.id = tbr.treatment_id'
            . ' LEFT JOIN his_co_treatment ct ON ct.id = tbr.co_treatment_id'
            . ' WHERE tbr.remove_time IS NULL AND tbr.is_delete = 0 AND ct.id IS NULL'
            . ' AND t.tdl_treatment_type_id IN (3, 4)'
            . ' AND (t.out_time IS NULL OR t.out_time > ?)'
            . ($loaiTru ? ' AND t.tdl_patient_type_id NOT IN (' . implode(',', array_fill(0, count($loaiTru), '?')) . ')' : '')
            . ' GROUP BY r.department_id';

        $bindings = array_merge([now()->format('YmdHis')], $loaiTru);

        return array_map(function ($d) {
            return (object) array_change_key_case((array) $d, CASE_LOWER);
        }, DB::connection('HISPro')->select($sql, $bindings));
    }

    /**
     * Hàm thuần, một lượt trên dữ liệu đã sắp theo treatment_id: gom khoảng theo (hồ sơ, đơn vị),
     * gộp khoảng chồng (nằm ghép), cắt theo [$tu, $den]. remove_time NULL = đang nằm → $den.
     * Ruling R9: hồ sơ đã có out_time thì lượt nằm kết thúc muộn nhất tại out_time (giường "treo").
     *
     * @param iterable $luot dòng truyVanLuotNam()
     * @param array $khoaDonVi [department_id => khoaDonVi]
     * @param int $tu epoch giây (đầu kỳ)
     * @param int $den epoch giây (min(to, now))
     * @return array [khoaDonVi => ngày-giường (float)]
     */
    public static function ngayGiuongTheoDonVi(iterable $luot, array $khoaDonVi, int $tu, int $den): array
    {
        $giay = [];
        $hoSo = null;
        $khoang = [];

        $boQua = 0;
        $xa = function () use (&$khoang, &$giay, $tu, $den) {
            foreach ($khoang as $dv => $ds) {
                $giay[$dv] = (isset($giay[$dv]) ? $giay[$dv] : 0) + TinhToan::giayTrongKy($ds, $tu, $den);
            }
            $khoang = [];
        };

        foreach ($luot as $d) {
            $d = (object) array_change_key_case((array) $d, CASE_LOWER);
            if ((string) $d->treatment_id !== $hoSo) {
                $xa();
                $hoSo = (string) $d->treatment_id;
            }
            $khoa = (int) $d->department_id;
            if (!isset($khoaDonVi[$khoa]) || $d->add_time === null || $d->add_time === '') {
                continue;
            }
            $vao = self::epochHoacNull((string) $d->add_time);
            $ra = self::rong($d->remove_time) ? $den : self::epochHoacNull((string) $d->remove_time);
            $raVien = isset($d->out_time) ? $d->out_time : null;
            $raVienEpoch = self::rong($raVien) ? null : self::epochHoacNull((string) $raVien);
            if ($vao === null || $ra === null || (!self::rong($raVien) && $raVienEpoch === null)) {
                $boQua++;
                continue;
            }
            if ($raVienEpoch !== null) {
                $ra = min($ra, $raVienEpoch);
            }
            if ($ra <= $vao) {
                continue;
            }
            $khoang[$khoaDonVi[$khoa]][] = [$vao, $ra];
        }
        $xa();

        if ($boQua > 0) {
            Log::debug('Dashboard lanh dao - bo qua luot nam co thoi gian khong hop le', ['so_luot' => $boQua]);
        }

        return array_map(function ($s) {
            return $s / 86400;
        }, $giay);
    }

    /**
     * @param array $ngayDieuTri dòng truyVanNgayDieuTri()
     * @param array $ngayGiuong [khoaDonVi => ngày-giường]
     * @param array $dangNam dòng truyVanDangNam()
     * @param array $danhMuc kết quả DanhMucDonVi::tai()
     * @param string|null $den mốc cắt (YmdHis) đã dùng khi tính $ngayGiuong; null = $ky->denThucTe()
     */
    public static function dinhDang(array $ngayDieuTri, array $ngayGiuong, array $dangNam, array $danhMuc, KyLoc $ky, string $den = null): array
    {
        $branch = $ky->branchIds();
        $thuoc = function ($dv) use ($danhMuc, $branch) {
            return isset($danhMuc['don_vi'][$dv]) && in_array((int) $danhMuc['don_vi'][$dv]['branch_id'], $branch, true);
        };
        $coGiuong = function ($dv) use ($danhMuc) {
            return (int) $danhMuc['don_vi'][$dv]['giuong_pd'] > 0;
        };

        // Ngày điều trị TB: gộp khoa con theo trọng số hồ sơ
        $gop = [];
        $ngoaiDanhMuc = 0;
        foreach ($ngayDieuTri as $d) {
            $d = (object) (array) $d;
            $khoa = (int) $d->last_department_id;
            if (!isset($danhMuc['khoa'][$khoa])) {
                $ngoaiDanhMuc += (int) $d->so_ho_so;
                continue;
            }
            $dv = $danhMuc['khoa'][$khoa];
            if (!$thuoc($dv)) {
                continue;
            }
            if (!isset($gop[$dv])) {
                $gop[$dv] = ['tong' => 0.0, 'so' => 0];
            }
            $gop[$dv]['tong'] += (float) $d->tong_ngay;
            $gop[$dv]['so'] += (int) $d->so_ho_so;
        }
        if ($ngoaiDanhMuc) {
            Log::debug('Dashboard lanh dao - ngay dieu tri: ho so khoa ngoai danh muc', ['so_ho_so' => $ngoaiDanhMuc, 'from' => $ky->from, 'to' => $ky->to]);
        }
        $cotNgay = [];
        foreach ($gop as $dv => $o) {
            if ($o['so'] <= 0) {
                continue;
            }
            $cotNgay[] = [
                'ten' => DanhMucDonVi::nhan($danhMuc['don_vi'][$dv], $ky->coSo),
                'gia_tri' => round($o['tong'] / $o['so'], 2),
                'so_ho_so' => $o['so'],
            ];
        }
        $cotNgay = TinhToan::sapGiam($cotNgay, 'gia_tri');

        // Công suất giường: chỉ đơn vị có giường phê duyệt
        $soNgay = self::soNgay($ky, $den);
        $cotCs = [];
        foreach ($danhMuc['don_vi'] as $dv => $donVi) {
            if (!$thuoc($dv) || !$coGiuong($dv)) {
                continue;
            }
            $pd = (int) $donVi['giuong_pd'];
            $ng = isset($ngayGiuong[$dv]) ? (float) $ngayGiuong[$dv] : 0.0;
            $cotCs[] = [
                'ten' => DanhMucDonVi::nhan($donVi, $ky->coSo),
                'gia_tri' => TinhToan::congSuat($ng, $pd, $soNgay),
                'ngay_giuong' => round($ng, 2),
                'giuong_pd' => $pd,
            ];
        }
        $cotCs = TinhToan::sapGiam($cotCs, 'gia_tri');

        return [
            'ngay_dieu_tri' => [
                'categories' => array_column($cotNgay, 'ten'),
                'gia_tri' => array_column($cotNgay, 'gia_tri'),
                'so_ho_so' => array_column($cotNgay, 'so_ho_so'),
            ],
            'cong_suat' => [
                'categories' => array_column($cotCs, 'ten'),
                'gia_tri' => array_column($cotCs, 'gia_tri'),
                'ngay_giuong' => array_column($cotCs, 'ngay_giuong'),
                'giuong_pd' => array_column($cotCs, 'giuong_pd'),
            ],
            'giuong_hien_tai' => self::giuongHienTai($dangNam, $danhMuc, $ky->coSo, $branch),
        ];
    }

    /**
     * Bảng giường hiện tại (ảnh chụp, không theo kỳ): chỉ đơn vị có giường PD; con_trong có thể âm.
     *
     * @param array $dangNam dòng truyVanDangNam()
     * @param int[] $branch
     */
    public static function giuongHienTai(array $dangNam, array $danhMuc, string $coSo, array $branch): array
    {
        $dangTheoDonVi = [];
        foreach ($dangNam as $d) {
            $d = (object) (array) $d;
            $khoa = (int) $d->department_id;
            if (isset($danhMuc['khoa'][$khoa])) {
                $dv = $danhMuc['khoa'][$khoa];
                $dangTheoDonVi[$dv] = (isset($dangTheoDonVi[$dv]) ? $dangTheoDonVi[$dv] : 0) + (int) $d->dang_nam;
            }
        }

        $bang = [];
        foreach ($danhMuc['don_vi'] as $dv => $donVi) {
            $pd = (int) $donVi['giuong_pd'];
            if ($pd <= 0 || !in_array((int) $donVi['branch_id'], $branch, true)) {
                continue;
            }
            $dang = isset($dangTheoDonVi[$dv]) ? $dangTheoDonVi[$dv] : 0;
            $bang[] = [
                'don_vi' => DanhMucDonVi::nhan($donVi, $coSo),
                'giuong_pd' => $pd,
                'dang_nam' => $dang,
                'con_trong' => $pd - $dang,
                'cong_suat' => TinhToan::tyLe($dang, $pd),
            ];
        }

        return TinhToan::sapGiam($bang, 'cong_suat');
    }

    /**
     * Hàm thuần cho thẻ số. Ngày ĐT gom theo branch của hồ sơ (spec §4); công suất = Σngày-giường
     * các đơn vị có giường PD của cơ sở ÷ (Σgiuong_pd × số ngày từ from đến $den).
     * Ruling R8: tử số chỉ gồm đơn vị có giuong_pd > 0 (thẻ = tổng các cột biểu đồ công suất).
     *
     * @param string|null $den mốc cắt (YmdHis) đã dùng khi tính $ngayGiuong; null = $ky->denThucTe()
     */
    public static function tongHop(array $ngayDieuTri, array $ngayGiuong, array $danhMuc, KyLoc $ky, string $den = null): array
    {
        $branchCoSo = [];
        foreach (config('dashboard_lanh_dao.co_so', []) as $ma => $cs) {
            $branchCoSo[(int) $cs['branch_id']] = $ma;
        }
        $rong = ['hn' => 0, 'nb' => 0];

        $tongNgay = $rong;
        $soHoSo = $rong;
        foreach ($ngayDieuTri as $d) {
            $d = (object) (array) $d;
            $b = (int) $d->branch_id;
            if (isset($branchCoSo[$b])) {
                $tongNgay[$branchCoSo[$b]] += (float) $d->tong_ngay;
                $soHoSo[$branchCoSo[$b]] += (int) $d->so_ho_so;
            }
        }

        $ngGiuong = $rong;
        $giuongPd = $rong;
        foreach ($danhMuc['don_vi'] as $dv => $donVi) {
            $b = (int) $donVi['branch_id'];
            if (!isset($branchCoSo[$b]) || (int) $donVi['giuong_pd'] <= 0) {
                continue;
            }
            $giuongPd[$branchCoSo[$b]] += (int) $donVi['giuong_pd'];
            $ngGiuong[$branchCoSo[$b]] += isset($ngayGiuong[$dv]) ? (float) $ngayGiuong[$dv] : 0.0;
        }

        $tb = function ($tong, $so) {
            return $so > 0 ? round($tong / $so, 2) : null;
        };
        $soNgay = self::soNgay($ky, $den);
        $kq = ['ngay_dieu_tri' => [], 'cong_suat_giuong' => []];
        foreach (['hn', 'nb'] as $cs) {
            $kq['ngay_dieu_tri'][$cs] = $tb($tongNgay[$cs], $soHoSo[$cs]);
            $kq['cong_suat_giuong'][$cs] = TinhToan::congSuat($ngGiuong[$cs], $giuongPd[$cs], $soNgay);
        }
        $kq['ngay_dieu_tri']['2cs'] = $tb(array_sum($tongNgay), array_sum($soHoSo));
        $kq['cong_suat_giuong']['2cs'] = TinhToan::congSuat(array_sum($ngGiuong), array_sum($giuongPd), $soNgay);

        return $kq;
    }

    /** YmdHis (giờ HIS, múi giờ ứng dụng) → epoch giây. Kỳ lọc và lượt nằm cùng đi qua hàm này. */
    public static function epoch(string $ymdHis): int
    {
        static $mui = null;
        if ($mui === null) {
            $mui = new \DateTimeZone(config('app.timezone'));
        }

        return \DateTime::createFromFormat('!YmdHis', $ymdHis, $mui)->getTimestamp();
    }

    /** Như epoch() nhưng trả null khi chuỗi không phải YmdHis hợp lệ (dữ liệu HIS hỏng không được làm sập cả tab). */
    private static function epochHoacNull(string $ymdHis)
    {
        static $mui = null;
        if ($mui === null) {
            $mui = new \DateTimeZone(config('app.timezone'));
        }
        $dt = \DateTime::createFromFormat('!YmdHis', $ymdHis, $mui);

        return $dt === false ? null : $dt->getTimestamp();
    }

    /** Số ngày từ from đến mốc cắt $den (mặc định denThucTe), cùng hàm epoch với tử số. */
    private static function soNgay(KyLoc $ky, string $den = null): float
    {
        return (self::epoch($den !== null ? $den : $ky->denThucTe()) - self::epoch($ky->from)) / 86400;
    }

    private static function rong($giaTri): bool
    {
        return $giaTri === null || $giaTri === '' || (string) $giaTri === '0';
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
