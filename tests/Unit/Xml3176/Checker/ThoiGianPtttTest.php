<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * XML3_EXECUTION_TIME_TOO_LONG va XML3_OVERLAPPING_SERVICE_EXECUTION (gop theo dong).
 * Ky vong viet tay tu mo ta da duyet 15/10/2026.
 */
class ThoiGianPtttTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const DAI = 'XML3_EXECUTION_TIME_TOO_LONG';
    const CHONG = 'XML3_OVERLAPPING_SERVICE_EXECUTION';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152832_create_xml3176_xml3s_table.php']);
    }

    private $stt = 0;

    private function x3(array $a)
    {
        return Xml3176Xml3::create($a + ['ma_lk' => 'L1', 'stt' => ++$this->stt, 'ma_nhom' => '18', 'ma_dich_vu' => '02.0150.0114']);
    }

    private function moTa($k, $method, $row, $ma)
    {
        return collect($this->invokePrivate($k, $method, $row))
            ->where('error_code', $ma)->pluck('description')->values()->all();
    }

    private function dai($k, $row)
    {
        return $this->moTa($k, 'checkExecutionTimeTooLong', $row, self::DAI);
    }

    /** @test */
    public function dung_bang_nguong_khong_loi_vuot_mot_phut_co_loi()
    {
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([], $this->dai($k, $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => '202601020800'])));
        $this->assertSame([
            'Dịch vụ 02.0150.0114 (01/01/2026 08:00 - 02/01/2026 08:01) kéo dài 24 giờ 1 phút, vượt ngưỡng 24 giờ. Kiểm tra lại NGAY_TH_YL / NGAY_KQ.',
        ], $this->dai($k, $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => '202601020801'])));
    }

    /** @test */
    public function chi_xet_nhom_cau_hinh_va_du_moc()
    {
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([], $this->dai($k, $this->x3(['ma_nhom' => '3', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601050800'])));
        $this->assertSame([], $this->dai($k, $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => null])));
        $this->assertSame([], $this->dai($k, $this->x3(['ngay_th_yl' => null, 'ngay_kq' => '202601050800'])));
    }

    /** @test */
    public function nguong_rieng_cvvhdf_va_vac()
    {
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([], $this->dai($k, $this->x3(['ma_dich_vu' => '01.0181.0118', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601030800'])));
        $this->assertSame([
            'Dịch vụ 01.0181.0118 (01/01/2026 08:00 - 04/01/2026 09:30) kéo dài 73 giờ 30 phút, vượt ngưỡng 72 giờ. Kiểm tra lại NGAY_TH_YL / NGAY_KQ.',
        ], $this->dai($k, $this->x3(['ma_dich_vu' => '01.0181.0118', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601040930'])));
        $this->assertCount(1, $this->dai($k, $this->x3(['ma_dich_vu' => '11.0118.1159', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601030801'])));
        $this->assertSame([], $this->dai($k, $this->x3(['ma_dich_vu' => '11.0118.1159', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601030800'])));
    }

    private function chong($k, $row)
    {
        return $this->moTa($k, 'checkOverlappingServiceExecution', $row, self::CHONG);
    }

    /** @test */
    public function mot_doi_tac_giu_cau_cu()
    {
        $a = $this->x3(['ma_dich_vu' => 'A', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $this->x3(['ma_dich_vu' => 'B', 'ngay_th_yl' => '202601010830', 'ngay_kq' => '202601010930']);
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([
            'Dịch vụ A (01/01/2026 08:00 - 01/01/2026 09:00) chồng thời gian thực hiện với dịch vụ B (01/01/2026 08:30 - 01/01/2026 09:30) trong cùng hồ sơ.',
        ], $this->chong($k, $a));
    }

    /** @test */
    public function qua_nam_doi_tac_ghi_so_con_lai()
    {
        $dau = $this->x3(['ma_dich_vu' => 'X', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601011200']);
        for ($i = 1; $i <= 7; $i++) {
            $this->x3(['ma_dich_vu' => 'P' . $i, 'ngay_th_yl' => '2026010108' . sprintf('%02d', $i * 7), 'ngay_kq' => '2026010108' . sprintf('%02d', $i * 7 + 5)]);
        }
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([
            'Dịch vụ X (01/01/2026 08:00 - 01/01/2026 12:00) chồng thời gian thực hiện với 7 dịch vụ khác trong cùng hồ sơ: '
            . 'P1 (01/01/2026 08:07 - 01/01/2026 08:12); P2 (01/01/2026 08:14 - 01/01/2026 08:19); '
            . 'P3 (01/01/2026 08:21 - 01/01/2026 08:26); P4 (01/01/2026 08:28 - 01/01/2026 08:33); '
            . 'P5 (01/01/2026 08:35 - 01/01/2026 08:40); và 2 dịch vụ khác.',
        ], $this->chong($k, $dau));
    }

    /** @test */
    public function dong_vuot_nguong_khong_so_chong()
    {
        $dai = $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => '202601100800']);
        $ngan = $this->x3(['ma_dich_vu' => 'A', 'ngay_th_yl' => '202601020800', 'ngay_kq' => '202601020815']);
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([], $this->chong($k, $dai));
        $this->assertSame([], $this->chong($k, $ngan));
        $this->assertCount(1, $this->dai($k, $dai));
    }

    /** @test */
    public function nhom_khac_khong_tinh_la_doi_tac()
    {
        $a = $this->x3(['ma_dich_vu' => 'A', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $this->x3(['ma_dich_vu' => 'E', 'ma_nhom' => '3', 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601010900']);
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertSame([], $this->chong($k, $a));
    }

    /**
     * @test
     * Dang du lieu 000007038497: 900 dong Hut dom cung NGAY_KQ cuoi dot. Cu: 404.550 loi.
     */
    public function ho_so_kieu_7038497_moi_dong_toi_da_mot_loi()
    {
        $ds = [];
        for ($i = 0; $i < 900; $i++) {
            $ngay = date('YmdHi', strtotime('2026-01-01 08:00') + $i * 3600);
            $ds[] = $this->x3(['ngay_th_yl' => $ngay, 'ngay_kq' => '202603010800']);
        }
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $tong = 0;
        $dai = 0;
        foreach ($ds as $d) {
            $tong += count($this->chong($k, $d));
            $dai += count($this->dai($k, $d));
        }

        $this->assertSame(0, $tong, 'moi dong deu vuot 24 gio nen khong vao so chong');
        $this->assertSame(900, $dai);
    }

    /** @test */
    public function ho_so_chong_day_dac_moi_dong_mot_loi()
    {
        $ds = [];
        for ($i = 0; $i < 300; $i++) {
            $ds[] = $this->x3(['ma_dich_vu' => 'P' . $i, 'ngay_th_yl' => '202601010800', 'ngay_kq' => '202601011000']);
        }
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        foreach ($ds as $d) {
            $this->assertCount(1, $this->chong($k, $d));
        }
        $this->assertContains('với 299 dịch vụ khác', $this->chong($k, $ds[0])[0]);
    }

    /** @test */
    public function nguong_doc_tu_cau_hinh()
    {
        config(['xml3176.xml3.execution_max_hours' => 2]);
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertCount(1, $this->dai($k, $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => '202601011001'])));
    }
}
