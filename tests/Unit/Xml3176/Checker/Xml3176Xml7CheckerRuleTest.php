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
        // 01/07..05/07 = 5 ngày (inclusive), khai 3 → sai
        $this->assertContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 3, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
    }

    /** @test */
    public function so_ngay_nghi_dung_thi_khong_bao()
    {
        $this->assertNotContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 5, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
    }

    /** @test */
    public function ngoai_tru_tu_ngay_truoc_ngay_ra()
    {
        $this->assertContains('XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA',
            $this->chay(['so_ngay_nghi' => 1, 'ngoaitru_tungay' => '20260630', 'ngoaitru_denngay' => '20260630', 'ngay_ra' => '20260701']));
    }
}
