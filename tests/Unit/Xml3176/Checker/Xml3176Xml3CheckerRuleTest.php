<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml3CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs, array $xml1 = null): array
    {
        $checker = $this->makeChecker(Xml3176Xml3Checker::class);
        $d = new Xml3176Xml3();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        if ($xml1 !== null) {
            $x1 = new Xml3176Xml1();
            foreach ($xml1 as $k => $v) {
                $x1->{$k} = $v;
            }
            $d->setRelation('Xml3176Xml1', $x1);
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkTimingAndExecutor', $d));
    }

    /** @test */
    public function tg_th_yl_trung_tg_kq()
    {
        $this->assertContains('XML3_NGAY_TH_YL_EQUALS_NGAY_KQ',
            $this->chay(['ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010900']));
    }

    /** @test */
    public function ngay_kq_lon_hon_ngay_ra()
    {
        $this->assertContains('XML3_NGAY_KQ_GREATER_NGAY_RA',
            $this->chay(['ngay_kq' => '202607060800'], ['ngay_ra' => '202607050800']));
    }

    /** @test */
    public function thuc_hien_duoi_3_phut_nhom_1()
    {
        $this->assertContains('XML3_EXECUTION_TIME_UNDER_3MIN',
            $this->chay(['ma_nhom' => '1', 'ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010901']));
    }

    /** @test */
    public function bac_si_vua_ra_vua_thuc_hien()
    {
        $this->assertContains('XML3_SAME_DOCTOR_ORDER_AND_EXECUTE',
            $this->chay(['ma_nhom' => '2', 'ma_bac_si' => 'BS01', 'nguoi_thuc_hien' => 'BS01']));
    }
}
