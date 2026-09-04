<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml8;
use App\Services\Xml3176Xml8Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml8CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(string $tomtat): array
    {
        $checker = $this->makeChecker(Xml3176Xml8Checker::class);
        $d = new Xml3176Xml8();
        $d->tomtat_kq = $tomtat;
        return $this->errorCodes($this->invokePrivate($checker, 'checkTomTatQuaNgan', $d));
    }

    /** @test */
    public function tom_tat_qua_ngan()
    {
        $this->assertContains('XML8_TOMTAT_KQ_TOO_SHORT', $this->chay('Ổn'));
    }

    /** @test */
    public function tom_tat_du_dai_thi_khong_bao()
    {
        $this->assertNotContains('XML8_TOMTAT_KQ_TOO_SHORT', $this->chay(str_repeat('Bệnh nhân ổn định. ', 3)));
    }
}
