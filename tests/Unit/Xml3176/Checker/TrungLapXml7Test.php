<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml7;
use App\Services\Xml3176Xml7Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026: tungay <= denngay < ngay_ra la MOT sai sot (dot nghi nam truoc
 * ngay ra) - chi bao NGOAITRU_TUNGAY_BEFORE_NGAY_RA. DENNGAY chi bao khi TUNGAY im lang.
 */
class TrungLapXml7Test extends TestCase
{
    use Xml3176RuleTestSupport;

    private function codes(array $attrs): array
    {
        $d = new Xml3176Xml7();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml7Checker::class), 'checkNghiNgoaiTru', $d
        ));
    }

    /** @test */
    public function ca_dot_nghi_truoc_ngay_ra_chi_mot_dong()
    {
        $codes = $this->codes(['ngoaitru_tungay' => '20260625', 'ngoaitru_denngay' => '20260628', 'ngay_ra' => '20260701']);

        $this->assertContains('XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA', $codes);
        $this->assertNotContains('XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA', $codes);
    }

    /** @test */
    public function chi_den_ngay_truoc_ngay_ra_van_bao_denngay()
    {
        // tungay hop le (>= ngay ra) nhung denngay < ngay ra (dao nguoc tu/den).
        $codes = $this->codes(['ngoaitru_tungay' => '20260705', 'ngoaitru_denngay' => '20260630', 'ngay_ra' => '20260701']);

        $this->assertNotContains('XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA', $codes);
        $this->assertContains('XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA', $codes);
    }

    /** @test */
    public function thieu_tu_ngay_van_bao_denngay()
    {
        $codes = $this->codes(['ngoaitru_tungay' => '', 'ngoaitru_denngay' => '20260630', 'ngay_ra' => '20260701']);

        $this->assertContains('XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA', $codes);
    }
}
