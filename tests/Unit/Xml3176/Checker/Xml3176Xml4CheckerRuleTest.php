<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176Xml4Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml4CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml4Checker::class);
        $d = new Xml3176Xml4();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkChiSo', $d));
    }

    /** @test */
    public function ma_chi_so_trong_khi_co_gia_tri()
    {
        $this->assertContains('XML4_MA_CHI_SO_EMPTY',
            $this->chay(['gia_tri' => '5.2', 'ma_chi_so' => '', 'ten_chi_so' => 'Glucose']));
    }

    /** @test */
    public function ten_chi_so_trong_khi_co_don_vi_do()
    {
        $this->assertContains('XML4_TEN_CHI_SO_EMPTY',
            $this->chay(['don_vi_do' => 'mmol/L', 'ma_chi_so' => 'GLU', 'ten_chi_so' => '']));
    }

    /** @test */
    public function xn_khong_gia_tri_khong_ket_qua()
    {
        $this->assertContains('XML4_XN_MISSING_VALUE_RESULT',
            $this->chay(['ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose', 'gia_tri' => '', 'mo_ta' => '', 'ket_luan' => '']));
    }

    /** @test */
    public function dong_hinh_anh_chi_co_mo_ta_khong_bao_thieu_chi_so()
    {
        $codes = $this->chay(['mo_ta' => 'Bình thường', 'ket_luan' => 'Không bất thường']);
        $this->assertNotContains('XML4_MA_CHI_SO_EMPTY', $codes);
        $this->assertNotContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }
}
