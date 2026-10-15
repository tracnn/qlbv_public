<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Jobs\CheckXml3176TypeJob;
use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Models\BHYT\Xml3176Xml5;
use App\Services\Xml3176ErrorService;
use App\Services\Xml3176Xml3Checker;
use App\Services\Xml3176Xml4Checker;
use App\Services\Xml3176Xml5Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Task 5: du lieu theo ho so (ma_lk) nap MOT lan moi job thay vi truy van lai moi dong.
 *
 * Cac test ket_qua_* duoc viet va chay xanh tren MA CU truoc khi doi (ghim hanh vi cu),
 * roi phai xanh y nguyen tren ma moi. Ky vong viet tay, khong suy ra tu ma.
 */
class TheoHoSoTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152843_create_xml3176_xml5s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
            '2026_09_10_100000_create_dvkt_can_ma_may_table.php',
        ]);
    }

    private function x3(array $a)
    {
        return Xml3176Xml3::create($a + ['ma_lk' => 'L1']);
    }

    private function x4(array $a)
    {
        return Xml3176Xml4::create($a + ['ma_lk' => 'L1']);
    }

    private function x5(array $a)
    {
        return Xml3176Xml5::create($a + ['ma_lk' => 'L1']);
    }

    /** Ma loi (va mo ta) thuoc nhom quan tam, giu thu tu. */
    private function loi($ds, $ma)
    {
        return collect($ds)->filter(function ($e) use ($ma) {
            return in_array($e->error_code, (array) $ma, true);
        })->map(function ($e) {
            return $e->error_code . ' | ' . $e->description;
        })->values()->all();
    }

    private function demTruyVanBang($bang, callable $fn)
    {
        $n = 0;
        DB::connection('mysql')->listen(function ($q) use (&$n, $bang) {
            if (strpos($q->sql, '"' . $bang . '"') !== false || strpos($q->sql, '`' . $bang . '`') !== false) {
                $n++;
            }
        });
        $fn();

        return $n;
    }

    // ─── XML4: DVKT XML3 bat buoc mo ta / ket luan ──────────────────────────

    const MA_KL = ['XML4_INFO_ERROR_MO_TA_EMPTY', 'XML4_INFO_ERROR_KET_LUAN_EMPTY'];

    private function duLieuXml4()
    {
        // ma_nhom 2 = bat buoc ket luan (config xml3176.xml4.xml3_ma_nhom_require_ket_luan)
        $this->x3(['stt' => 1, 'ma_dich_vu' => 'DV1', 'ma_nhom' => '2']);
        $this->x3(['stt' => 2, 'ma_dich_vu' => 'DV2', 'ma_nhom' => '3']);
        $this->x3(['stt' => 3, 'ma_dich_vu' => 'DV1', 'ma_nhom' => '3']); // trung ma, nhom khac
        $this->x3(['stt' => 4, 'ma_dich_vu' => null, 'ma_nhom' => '2']);
        $this->x3(['ma_lk' => 'L2', 'stt' => 1, 'ma_dich_vu' => 'DV3', 'ma_nhom' => '2']);
    }

    /** @test */
    public function xml4_ket_qua_giong_truy_van_cu()
    {
        $this->duLieuXml4();
        $c = $this->makeChecker(Xml3176Xml4Checker::class);

        $kq = function ($row) use ($c) {
            return $this->loi($this->invokePrivate($c, 'infoChecker', $row), self::MA_KL);
        };

        $this->assertSame([
            'XML4_INFO_ERROR_MO_TA_EMPTY | Mô tả không được để trống đối với DVKT: DV1',
            'XML4_INFO_ERROR_KET_LUAN_EMPTY | Kết luận không được để trống đối với DVKT: DV1',
        ], $kq($this->x4(['stt' => 1, 'ma_dich_vu' => 'DV1'])));
        $this->assertSame([
            'XML4_INFO_ERROR_KET_LUAN_EMPTY | Kết luận không được để trống đối với DVKT: DV1',
        ], $kq($this->x4(['stt' => 2, 'ma_dich_vu' => 'DV1', 'mo_ta' => 'co'])));
        $this->assertSame([], $kq($this->x4(['stt' => 3, 'ma_dich_vu' => 'DV1', 'mo_ta' => 'co', 'ket_luan' => 'co'])));
        $this->assertSame([], $kq($this->x4(['stt' => 4, 'ma_dich_vu' => 'DV2'])), 'nhom 3 khong bat buoc');
        $this->assertSame([], $kq($this->x4(['stt' => 5, 'ma_dich_vu' => 'DV3'])), 'DV3 thuoc ho so khac');
        $this->assertSame([], $kq($this->x4(['stt' => 6, 'ma_dich_vu' => 'DV9'])));
    }

    /** @test */
    public function xml4_doi_ma_lk_khong_ro_du_lieu()
    {
        $this->duLieuXml4();
        $c = $this->makeChecker(Xml3176Xml4Checker::class);

        // Ho so 1 truoc (nap tap DV1), ho so 2 sau tren CUNG checker.
        $this->invokePrivate($c, 'infoChecker', $this->x4(['stt' => 1, 'ma_dich_vu' => 'DV1']));
        $l2DV1 = $this->x4(['ma_lk' => 'L2', 'stt' => 1, 'ma_dich_vu' => 'DV1']);
        $l2DV3 = $this->x4(['ma_lk' => 'L2', 'stt' => 2, 'ma_dich_vu' => 'DV3']);

        $this->assertSame([], $this->loi($this->invokePrivate($c, 'infoChecker', $l2DV1), self::MA_KL));
        $this->assertCount(2, $this->loi($this->invokePrivate($c, 'infoChecker', $l2DV3), self::MA_KL));
        // Quay lai ho so 1: nap lai, khong dung tap cua ho so 2.
        $this->assertSame([], $this->loi($this->invokePrivate($c, 'infoChecker',
            $this->x4(['stt' => 9, 'ma_dich_vu' => 'DV3'])), self::MA_KL));
    }

    // ─── XML3: PTTT chong thoi gian (so voi dong id lon hon) ─────────────────

    private function duLieuChong()
    {
        $a = $this->x3(['stt' => 1, 'ma_dich_vu' => 'A', 'ma_nhom' => '8', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $b = $this->x3(['stt' => 2, 'ma_dich_vu' => 'B', 'ma_nhom' => '18', 'ngay_th_yl' => '202601010830', 'ngay_kq' => '202601010930']);
        $c = $this->x3(['stt' => 3, 'ma_dich_vu' => 'C', 'ma_nhom' => '8', 'ngay_th_yl' => '202601010845', 'ngay_kq' => '202601010850']);
        $this->x3(['stt' => 4, 'ma_dich_vu' => 'D', 'ma_nhom' => '8', 'ngay_th_yl' => '202601010800', 'ngay_kq' => null]);
        $this->x3(['stt' => 5, 'ma_dich_vu' => 'E', 'ma_nhom' => '3', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $this->x3(['stt' => 6, 'ma_dich_vu' => 'F', 'ma_nhom' => '8', 'ngay_th_yl' => '202601011000', 'ngay_kq' => '202601011100']);
        $this->x3(['ma_lk' => 'L2', 'stt' => 1, 'ma_dich_vu' => 'G', 'ma_nhom' => '8', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);

        return [$a, $b, $c];
    }

    private function chong($checker, $row)
    {
        return $this->loi($this->invokePrivate($checker, 'checkOverlappingServiceExecution', $row),
            'XML3_OVERLAPPING_SERVICE_EXECUTION');
    }

    /** @test */
    public function xml3_chong_thoi_gian_ket_qua_giong_truy_van_cu()
    {
        list($a, $b, $c) = $this->duLieuChong();
        $k = $this->makeChecker(Xml3176Xml3Checker::class);
        $p = 'XML3_OVERLAPPING_SERVICE_EXECUTION | ';

        // Thu tu theo id tang dan (truy van cu khong co orderBy).
        $this->assertSame([
            $p . 'Dịch vụ A (01/01/2026 08:00 - 01/01/2026 09:00) chồng thời gian thực hiện với dịch vụ B (01/01/2026 08:30 - 01/01/2026 09:30) trong cùng hồ sơ.',
            $p . 'Dịch vụ A (01/01/2026 08:00 - 01/01/2026 09:00) chồng thời gian thực hiện với dịch vụ C (01/01/2026 08:45 - 01/01/2026 08:50) trong cùng hồ sơ.',
        ], $this->chong($k, $a));
        $this->assertSame([
            $p . 'Dịch vụ B (01/01/2026 08:30 - 01/01/2026 09:30) chồng thời gian thực hiện với dịch vụ C (01/01/2026 08:45 - 01/01/2026 08:50) trong cùng hồ sơ.',
        ], $this->chong($k, $b));
        $this->assertSame([], $this->chong($k, $c), 'chi so voi id lon hon');
    }

    /** @test */
    public function xml3_chong_thoi_gian_doi_ma_lk_khong_ro_du_lieu()
    {
        list($a) = $this->duLieuChong();
        $k = $this->makeChecker(Xml3176Xml3Checker::class);
        $this->chong($k, $a);

        // Dong cua L2 co id lon nhat; L1 co dong chong voi no nhung id nho hon - va khac ho so.
        $g = Xml3176Xml3::where('ma_lk', 'L2')->first();
        $this->assertSame([], $this->chong($k, $g));
        $h = $this->x3(['ma_lk' => 'L3', 'stt' => 1, 'ma_dich_vu' => 'H', 'ma_nhom' => '8', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $this->assertSame([], $this->chong($k, $h));
    }

    // ─── XML3: VTYT kem DVKT phai co ngay y lenh khop ────────────────────────

    private function duLieuNgayVatTu()
    {
        // Dong DVKT (ma_vat_tu rong)
        $this->x3(['stt' => 1, 'ma_dich_vu' => 'DV1', 'ma_vat_tu' => '', 'ma_nhom' => '3', 'ngay_yl' => '202601010800']);
        $this->x3(['stt' => 2, 'ma_dich_vu' => 'DV1', 'ma_vat_tu' => '', 'ma_nhom' => '3', 'ngay_yl' => '202601030800']);
        $this->x3(['stt' => 3, 'ma_dich_vu' => 'DV5', 'ma_vat_tu' => null, 'ma_nhom' => '3', 'ngay_yl' => '202601010800']);
        $this->x3(['ma_lk' => 'L2', 'stt' => 1, 'ma_dich_vu' => 'DV3', 'ma_vat_tu' => '', 'ma_nhom' => '3', 'ngay_yl' => '202601010800']);
    }

    private function vatTu($stt, $maDv, $ngay, $maLk = 'L1')
    {
        // tt_thau rong -> sai dinh dang, khong tra danh muc VTYT.
        return $this->x3(['ma_lk' => $maLk, 'stt' => $stt, 'ma_dich_vu' => $maDv, 'ma_vat_tu' => 'VT1',
            'ten_vat_tu' => 'x', 'ma_nhom' => '10', 'ngay_yl' => $ngay, 'tt_thau' => '']);
    }

    private function ngayVt($checker, $row)
    {
        return $this->loi($this->invokePrivate($checker, 'checkMedicalSupplyCatalog', $row), 'XML3_DATE_MISMATCH');
    }

    /** @test */
    public function xml3_ngay_vat_tu_ket_qua_giong_truy_van_cu()
    {
        $this->duLieuNgayVatTu();
        $k = $this->makeChecker(Xml3176Xml3Checker::class);
        $sai = function ($dv) {
            return ['XML3_DATE_MISMATCH | Mã dịch vụ: ' . $dv . '; Không tìm thấy ngày y lệnh khớp với vật tư: VT1'];
        };

        $this->assertSame([], $this->ngayVt($k, $this->vatTu(11, 'DV1', '202601031000')));
        $this->assertSame([], $this->ngayVt($k, $this->vatTu(12, 'DV1', '202601010000')));
        $this->assertSame($sai('DV1'), $this->ngayVt($k, $this->vatTu(13, 'DV1', '202601020800')));
        $this->assertSame($sai('DV2'), $this->ngayVt($k, $this->vatTu(14, 'DV2', '202601010800')));
        $this->assertSame($sai('DV3'), $this->ngayVt($k, $this->vatTu(15, 'DV3', '202601010800')), 'DV3 thuoc ho so khac');
        $this->assertSame($sai('DV5'), $this->ngayVt($k, $this->vatTu(16, 'DV5', '202601010800')), 'ma_vat_tu NULL khong phai rong');
        $this->assertSame([], $this->ngayVt($k, $this->vatTu(17, '', '202601010800')), 'ma dich vu rong khong xet');
    }

    /** @test */
    public function xml3_ngay_vat_tu_doi_ma_lk_khong_ro_du_lieu()
    {
        $this->duLieuNgayVatTu();
        $k = $this->makeChecker(Xml3176Xml3Checker::class);
        $this->assertSame([], $this->ngayVt($k, $this->vatTu(11, 'DV1', '202601031000')));

        $this->assertCount(1, $this->ngayVt($k, $this->vatTu(1, 'DV1', '202601031000', 'L3')));
        $this->assertSame([], $this->ngayVt($k, $this->vatTu(2, 'DV3', '202601011000', 'L2')));
    }

    // ─── XML5: dien bien trung trong cung ngay ───────────────────────────────

    private function duLieuDienBien()
    {
        $r = [];
        $r[1] = $this->x5(['stt' => 1, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601010800']);
        $r[2] = $this->x5(['stt' => 2, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601011000']);
        $r[3] = $this->x5(['stt' => 3, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601020800']);
        // Khoang trang dau: datePart van ra 20260101 nhung LIKE '20260101%' khong khop.
        $r[4] = $this->x5(['stt' => 4, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => ' 202601010900']);
        $r[5] = $this->x5(['stt' => 5, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601011200']);
        $r[6] = $this->x5(['stt' => 6, 'dien_bien_ls' => 'Khac', 'thoi_diem_dbls' => '202601011300']);
        $r[7] = $this->x5(['stt' => 7, 'dien_bien_ls' => 'Khac', 'thoi_diem_dbls' => null]);
        $this->x5(['ma_lk' => 'L2', 'stt' => 4, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601011100']);

        return $r;
    }

    private function dienBien($checker, $row)
    {
        return $this->loi($this->invokePrivate($checker, 'checkDienBienDuplicate', $row), 'XML5_DIEN_BIEN_DUPLICATE');
    }

    /** @test */
    public function xml5_ket_qua_giong_truy_van_cu()
    {
        $r = $this->duLieuDienBien();
        $k = $this->makeChecker(Xml3176Xml5Checker::class);
        $trung = function ($stt) {
            return ['XML5_DIEN_BIEN_DUPLICATE | Diễn biến lâm sàng ngày 01/01/2026 trùng với dòng STT ' . $stt . ' cùng ngày: "On dinh"'];
        };

        $this->assertSame($trung(2), $this->dienBien($k, $r[5]), 'stt lon nhat cung ngay, bo dong 4 (khoang trang dau)');
        $this->assertSame($trung(1), $this->dienBien($k, $r[2]));
        $this->assertSame([], $this->dienBien($k, $r[1]));
        $this->assertSame([], $this->dienBien($k, $r[3]), 'khac ngay');
        $this->assertSame($trung(2), $this->dienBien($k, $r[4]), 'datePart cua dong 4 bo khoang trang -> so voi stt 1,2');
    }

    /** @test */
    public function xml5_doi_ma_lk_khong_ro_du_lieu()
    {
        $r = $this->duLieuDienBien();
        $k = $this->makeChecker(Xml3176Xml5Checker::class);
        $this->dienBien($k, $r[5]);

        $l2 = Xml3176Xml5::where('ma_lk', 'L2')->first();
        $this->assertSame([], $this->dienBien($k, $l2), 'L1 co stt 1,2 cung ngay nhung khac ho so');
        $l3 = $this->x5(['ma_lk' => 'L3', 'stt' => 9, 'dien_bien_ls' => 'On dinh', 'thoi_diem_dbls' => '202601011500']);
        $this->assertSame([], $this->dienBien($k, $l3));
    }

    // ─── Job: XML1 nap mot lan, ket qua job khong doi ───────────────────────

    /** ErrorService gia: gom loi vao $daGhi thay vi ghi CSDL. */
    private function loiGia()
    {
        $loi = new class extends FakeXml3176ErrorService {
            public $daGhi = [];
            public function ketThucGom(): void
            {
                $this->daGhi = array_merge($this->daGhi, $this->boDemGom());
                parent::batDauGom();
                parent::ketThucGom();
            }
        };
        $this->app->instance(Xml3176ErrorService::class, $loi);

        return $loi;
    }

    /** @test */
    public function xml1_nap_mot_lan_khi_job_kiem_50_dong_xml5()
    {
        Xml3176Xml1::create(['ma_lk' => 'L1', 'stt' => 1, 'ngay_vao' => '202601010700', 'ngay_ra' => '202601050700']);
        for ($i = 1; $i <= 50; $i++) {
            $this->x5(['stt' => $i, 'dien_bien_ls' => 'Dien bien ' . ($i % 7), 'thoi_diem_dbls' => '2026010' . (1 + $i % 6) . '0800']);
        }
        $loi = $this->loiGia();

        $n = $this->demTruyVanBang('xml3176_xml1s', function () {
            (new CheckXml3176TypeJob('L1', 'XML5'))->handle();
        });

        $this->assertSame(1, $n);
        // Dong ngoai khoang vao-ra van bi bat: XML1 that su duoc dung.
        $ma = array_column($loi->daGhi, 'error_code');
        $this->assertContains('XML5_INVALID_THOI_DIEM_BDLS', $ma);
        $this->assertContains('XML5_DIEN_BIEN_DUPLICATE', $ma);
    }

    /** @test */
    public function xml5_nap_mot_lan_khi_job_kiem_50_dong()
    {
        for ($i = 1; $i <= 50; $i++) {
            $this->x5(['stt' => $i, 'dien_bien_ls' => 'Dien bien ' . ($i % 7), 'thoi_diem_dbls' => '2026010' . (1 + $i % 6) . '0800']);
        }
        $this->loiGia();

        $n = $this->demTruyVanBang('xml3176_xml5s', function () {
            (new CheckXml3176TypeJob('L1', 'XML5'))->handle();
        });

        // 1 truy van chunk (50 < lo 500) + 1 truy van tap dong cua ho so.
        $this->assertSame(2, $n);
    }

    /** @test */
    public function xml4_tap_dvkt_xml3_nap_mot_lan_cho_50_dong()
    {
        $this->duLieuXml4();
        $c = $this->makeChecker(Xml3176Xml4Checker::class);
        $dong = [];
        for ($i = 1; $i <= 50; $i++) {
            $dong[] = $this->x4(['stt' => $i, 'ma_dich_vu' => $i % 2 ? 'DV1' : 'DV2']);
        }

        $n = $this->demTruyVanBang('xml3176_xml3s', function () use ($c, $dong) {
            foreach ($dong as $d) {
                $this->invokePrivate($c, 'infoChecker', $d);
            }
        });

        $this->assertSame(1, $n);
    }

    /** @test */
    public function xml3_tap_theo_ho_so_nap_mot_lan_cho_50_dong()
    {
        $this->duLieuNgayVatTu();
        $k = $this->makeChecker(Xml3176Xml3Checker::class);
        $dong = [];
        for ($i = 1; $i <= 50; $i++) {
            $dong[] = $this->x3(['stt' => 100 + $i, 'ma_dich_vu' => 'DV1', 'ma_vat_tu' => 'VT1', 'ten_vat_tu' => 'x',
                'ma_nhom' => '10', 'ngay_yl' => '202601010800', 'tt_thau' => '']);
            $dong[] = $this->x3(['stt' => 200 + $i, 'ma_dich_vu' => 'P', 'ma_nhom' => '8',
                'ngay_th_yl' => '20260101' . sprintf('%02d', 8 + $i % 10) . '00', 'ngay_kq' => '20260101' . sprintf('%02d', 8 + $i % 10) . '30']);
        }

        $n = $this->demTruyVanBang('xml3176_xml3s', function () use ($k, $dong) {
            foreach ($dong as $d) {
                $this->invokePrivate($k, 'checkMedicalSupplyCatalog', $d);
                $this->invokePrivate($k, 'checkOverlappingServiceExecution', $d);
            }
        });

        $this->assertSame(2, $n, 'mot truy van DVKT khong vat tu + mot truy van PTTT co moc');
    }

    /** @test */
    public function xml1_nap_mot_lan_khi_job_kiem_50_dong_xml3()
    {
        Xml3176Xml1::create(['ma_lk' => 'L1', 'stt' => 1, 'ma_cskcb' => '01929', 'ma_loai_kcb' => '03',
            'ngay_vao' => '202601010700', 'ngay_ra' => '202601050700']);
        for ($i = 1; $i <= 50; $i++) {
            $this->x3(['stt' => $i, 'ma_dich_vu' => '01.0001.000' . ($i % 3), 'ten_dich_vu' => 'DV', 'ma_vat_tu' => '',
                'ma_nhom' => '3', 'so_luong' => 1, 'don_gia_bv' => 0, 'don_gia_bh' => 0, 'tyle_tt_dv' => 100, 'tyle_tt_bh' => 100,
                'ngay_yl' => '202601020800', 'ngay_th_yl' => '202601020810', 'ngay_kq' => '202601020900', 'ma_khoa' => 'K01']);
        }
        $loi = $this->loiGia();

        $n = $this->demTruyVanBang('xml3176_xml1s', function () {
            (new CheckXml3176TypeJob('L1', 'XML3'))->handle();
        });

        $this->assertSame(1, $n);
        $this->assertNotEmpty($loi->daGhi);
    }

    /** @test */
    public function truy_van_dong_nap_kem_xml1_dung_cac_loai_co_quan_he()
    {
        $kem = [];
        foreach (\App\Services\Xml3176\Xml3176CheckTypes::LOAI as $loai => $ch) {
            if (array_key_exists('Xml3176Xml1', \App\Services\Xml3176\Xml3176CheckTypes::truyVanDong($loai, 'L1')->getEagerLoads())) {
                $kem[] = $loai;
            }
        }

        $this->assertSame(['XML2', 'XML3', 'XML4', 'XML5', 'XML11'], $kem);
    }

    /** @test */
    public function khoa_chuoi_theo_ngu_nghia_bang_cua_mysql()
    {
        // Ghim ngu nghia '=' cua MySQL utf8_general_ci (PAD SPACE) ma truy van cu dua vao.
        // SQLite cua test phan biet hoa thuong nen khong kiem duoc diem nay qua truy van that.
        $m = new \ReflectionMethod(Xml3176Xml4Checker::class, 'khoaChuoiSql');
        $m->setAccessible(true);
        $k = function ($s) use ($m) {
            return $m->invoke(null, $s);
        };

        $this->assertSame($k('DV1'), $k('dv1'));
        $this->assertSame($k('DV1'), $k('DV1   '));
        $this->assertNotSame($k('DV1'), $k(' DV1'), 'khoang trang dau co nghia');
        $this->assertNotSame($k('DV1'), $k("DV1\t"), 'chi bo dau cach cuoi');
        $this->assertNotSame($k('DV1'), $k('DV2'));
    }
}
