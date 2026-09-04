<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml1CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml1Checker::class);
        $d = new Xml3176Xml1();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkNgaySinhVsNgayVao', $d));
    }

    /** @test */
    public function bat_loi_ngay_sinh_lon_hon_ngay_vao()
    {
        $this->assertContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '20000101', 'ngay_vao' => '199001010800']));
    }

    /** @test */
    public function khong_bat_khi_ngay_sinh_nho_hon()
    {
        $this->assertNotContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '19800101', 'ngay_vao' => '202607010800']));
    }

    /** @test */
    public function khong_bat_khi_thieu_ngay()
    {
        $this->assertNotContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '', 'ngay_vao' => '202607010800']));
    }
}
