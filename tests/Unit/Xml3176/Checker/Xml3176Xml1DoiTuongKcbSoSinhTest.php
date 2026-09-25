<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ma doi tuong 1.7 "Tre so sinh phai dieu tri ngay sau khi sinh ra" chi hop le voi nguoi
 * benh <= 28 ngay tuoi luc vao vien.
 *
 * BHXH tra loi ho so 000007199029: nguoi benh sinh 07/05/1973 khai 1.7. Tren du lieu that
 * 42 ho so khai 1.7: 40 tre vao vien ngay trong ngay sinh (0 ngay tuoi), 2 nguoi lon.
 */
class Xml3176Xml1DoiTuongKcbSoSinhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML1_DOI_TUONG_KCB_KHONG_PHAI_SO_SINH';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
    }

    private function loi(array $ghiDe)
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '1.7',
            'ma_the_bhyt' => 'TE1373700012345', 'ma_cskcb' => '01929', 'ma_dkbd' => '37000',
            'ma_loai_kcb' => '03', 'ngay_sinh' => '202609171246', 'ngay_vao' => '202609171245',
        ], $ghiDe));

        return collect($this->invokePrivate($this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong))
            ->where('error_code', self::MA)->values();
    }

    /** @test */
    public function tre_vao_vien_ngay_trong_ngay_sinh_khong_bao()
    {
        $this->assertCount(0, $this->loi([]));
    }

    /** @test */
    public function nguoi_lon_khai_17_bi_bao_va_neu_ngay_sinh()
    {
        // Dung ca 000007199029.
        $loi = $this->loi(['ngay_sinh' => '197305070000', 'ngay_vao' => '202609090831', 'ma_the_bhyt' => 'DN4010112345678']);

        $this->assertCount(1, $loi);
        $this->assertSame('Không phải đối tượng trẻ sơ sinh phải điều trị ngay sau khi sinh ra', $loi[0]->error_name);
        $this->assertContains('07/05/1973', $loi[0]->description);
        $this->assertContains('tối đa 28 ngày', $loi[0]->description);
    }

    /** @test */
    public function dung_28_ngay_khong_bao_29_ngay_thi_bao()
    {
        $this->assertCount(0, $this->loi(['ngay_sinh' => '202608200000', 'ngay_vao' => '202609170800']));  // 28 ngay
        $this->assertCount(1, $this->loi(['ngay_sinh' => '202608190000', 'ngay_vao' => '202609170800']));  // 29 ngay
    }

    /** @test */
    public function ngay_sinh_chi_co_nam_hoac_ngay_vao_hong_thi_khong_bao()
    {
        $this->assertCount(0, $this->loi(['ngay_sinh' => '197300000000']));
        $this->assertCount(0, $this->loi(['ngay_sinh' => '197305070000', 'ngay_vao' => '']));
    }

    /** @test */
    public function ma_khac_17_khong_ap_dung()
    {
        $this->assertCount(0, $this->loi(['ma_doituong_kcb' => '1.1', 'ngay_sinh' => '197305070000', 'ma_dkbd' => '01929']));
    }
}
