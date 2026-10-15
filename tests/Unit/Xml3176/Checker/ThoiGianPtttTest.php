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

    /** @test */
    public function nguong_doc_tu_cau_hinh()
    {
        config(['xml3176.xml3.execution_max_hours' => 2]);
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        $this->assertCount(1, $this->dai($k, $this->x3(['ngay_th_yl' => '202601010800', 'ngay_kq' => '202601011001'])));
    }
}
