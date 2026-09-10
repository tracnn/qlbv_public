<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteCheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    /** @test */
    public function thieu_chuyen_tuyen_va_hen_kham_lai()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'A', 'stt' => 1, 'ma_noi_di' => '01001']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMissingTransferOrAppointment', $x1));
        $this->assertContains('XMLComplete_MISSING_TRANSFER_OR_APPOINTMENT', $codes);
    }

    /** @test */
    public function ngay_kq_xml4_khac_xml3()
    {
        Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607010900']);
        Xml3176Xml4::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607020900']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkXml4NgayKqMismatchXml3', 'B'));
        $this->assertContains('XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3', $codes);
    }

    /** @test */
    public function pt_lan_2_thanh_toan_100()
    {
        Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 1, 'ma_dich_vu' => 'PT', 'ma_nhom' => 8, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 2, 'ma_dich_vu' => 'PT', 'ma_nhom' => 18, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'C'));
        $this->assertContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }

    /** @test */
    public function dong_ngoai_nhom_pttt_khong_bi_coi_la_pttt_du_co_ma_pttt()
    {
        // Bo xuat HIS dien MA_PTTT cho gan nhu moi dong: do tren du lieu that, 28.169
        // dong co MA_PTTT nhung chi 2.487 (8,8%) thuoc nhom 8/18. Can cu theo MA_PTTT
        // lam quy tac bao "PTTT lan 2..100 trong ngay" cho ca xet nghiem, VTYT, giuong -
        // 24.954 loi tren 914/1153 ho so, gan het la bao oan.
        Xml3176Xml1::create(['ma_lk' => 'XN', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 2, 'ma_dich_vu' => 'XN2', 'ma_nhom' => 1, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 3, 'ma_dich_vu' => 'VT1', 'ma_nhom' => 10, 'ma_pttt' => 'PT03', 'ngay_yl' => '202607011200', 'tyle_tt_dv' => '100']);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'XN'));

        $this->assertNotContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }

    /** @test */
    public function chi_dem_dong_thuoc_nhom_pttt_khi_xep_thu_tu_trong_ngay()
    {
        // Dong xet nghiem xen giua khong duoc lam dong PTTT thu hai bi tinh thanh thu ba,
        // va mot minh dong PTTT duy nhat trong ngay thi khong co "lan 2" nao.
        Xml3176Xml1::create(['ma_lk' => 'MOT', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'MOT', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'MOT', 'stt' => 2, 'ma_dich_vu' => 'PT1', 'ma_nhom' => 8, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'MOT'));

        $this->assertNotContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }
}
