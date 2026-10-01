<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml7;
use App\Services\Xml3176Xml7Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml7CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml7Checker::class);
        $d = new Xml3176Xml7();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkNghiNgoaiTru', $d));
    }

    /** @test */
    public function so_ngay_nghi_sai()
    {
        // 01/07..05/07: den - tu = 4 ngay, khai 3 → sai
        $this->assertContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 3, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
    }

    /** @test */
    public function so_ngay_nghi_dung_thi_khong_bao()
    {
        // So ngay nghi = den - tu (khong cong 1). Do 01/10/2026: ca 37/37 dong XML7 tren CSDL
        // that khai theo cach nay (vd 000007181552: 01/10..09/10 khai 8) - ban +1 cu bao sai
        // ca 37 dong o muc nghiem trong. Nguoi dung chot: den - tu.
        $this->assertNotContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 4, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
        $this->assertNotContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 8, 'ngoaitru_tungay' => '202610010000', 'ngoaitru_denngay' => '202610090000', 'ngay_ra' => '202609301449']));
    }

    /** @test */
    public function so_ngay_nghi_tinh_ca_hai_dau_thi_bao()
    {
        $loi = $this->chay(['so_ngay_nghi' => 5, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']);

        $this->assertContains('XML7_SO_NGAY_NGHI_MISMATCH', $loi);
    }

    /** @test */
    public function ngoai_tru_tu_ngay_truoc_ngay_ra()
    {
        $this->assertContains('XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA',
            $this->chay(['so_ngay_nghi' => 1, 'ngoaitru_tungay' => '20260630', 'ngoaitru_denngay' => '20260630', 'ngay_ra' => '20260701']));
    }
}
