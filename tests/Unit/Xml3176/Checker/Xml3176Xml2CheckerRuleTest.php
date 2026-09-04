<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml2;
use App\Services\Xml3176Xml2Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml2CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml2Checker::class);
        $d = new Xml3176Xml2();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkLieuDung', $d));
    }

    /** @test */
    public function sai_dinh_dang_130()
    {
        $this->assertContains('XML2_LIEU_DUNG_INVALID_FORMAT',
            $this->chay(['lieu_dung' => '1 viên x 2 lần', 'ten_thuoc' => 'Paracetamol']));
    }

    /** @test */
    public function ke_qua_30_ngay()
    {
        $this->assertContains('XML2_PRESCRIPTION_EXCEEDS_30_DAYS',
            $this->chay(['lieu_dung' => '1*1*45', 'so_luong' => 45]));
    }

    /** @test */
    public function tong_lieu_khac_so_luong()
    {
        $this->assertContains('XML2_LIEU_DUNG_QUANTITY_MISMATCH',
            $this->chay(['lieu_dung' => '1*2*5', 'so_luong' => 8])); // tong=10 != 8
    }

    /** @test */
    public function tong_lieu_khop_thi_khong_bao()
    {
        $this->assertNotContains('XML2_LIEU_DUNG_QUANTITY_MISMATCH',
            $this->chay(['lieu_dung' => '1*2*5', 'so_luong' => 10]));
    }

    /** @test */
    public function don_vi_lieu_khac_don_vi_thuoc()
    {
        $this->assertContains('XML2_LIEU_DUNG_UNIT_INVALID',
            $this->chay(['lieu_dung' => '1*1*3 [Ống/ngày]', 'so_luong' => 3, 'don_vi_tinh' => 'Viên']));
    }
}
