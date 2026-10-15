<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176Xml4Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml4CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        // Ra soat 15/10/2026: XN_MISSING_VALUE_RESULT nay tra tap DVKT bat buoc ket luan
        // (XML3 cung ho so) de nhuong MO_TA/KET_LUAN_EMPTY - can bang xml3s in-memory.
        $this->bootXml3176Sqlite(['2026_01_09_152832_create_xml3176_xml3s_table.php']);
    }

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml4Checker::class);
        $d = new Xml3176Xml4();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkChiSo', $d));
    }

    private function moTa(array $attrs, string $ma): array
    {
        $checker = $this->makeChecker(Xml3176Xml4Checker::class);
        $d = new Xml3176Xml4();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }

        return collect($this->invokePrivate($checker, 'checkChiSo', $d))
            ->where('error_code', $ma)->pluck('description')->values()->all();
    }

    /** @test */
    public function ma_chi_so_trong_khi_co_gia_tri()
    {
        $this->assertSame(['Dòng có giá trị/đơn vị đo nhưng thiếu mã chỉ số. Dịch vụ: XN1'],
            $this->moTa(['ma_dich_vu' => 'XN1', 'gia_tri' => '5.2', 'ma_chi_so' => '', 'ten_chi_so' => 'Glucose'], 'XML4_CHI_SO_EMPTY'));
    }

    /** @test */
    public function ten_chi_so_trong_khi_co_don_vi_do()
    {
        $this->assertSame(['Dòng có giá trị/đơn vị đo nhưng thiếu tên chỉ số. Dịch vụ: XN1'],
            $this->moTa(['ma_dich_vu' => 'XN1', 'don_vi_do' => 'mmol/L', 'ma_chi_so' => 'GLU', 'ten_chi_so' => ''], 'XML4_CHI_SO_EMPTY'));
    }

    /**
     * @test
     * Ra soat 15/10/2026: 350.508 dong XML4 bao ca MA_ lan TEN_CHI_SO_EMPTY - nay mot dong.
     */
    public function thieu_ca_ma_va_ten_chi_mot_loi()
    {
        $codes = $this->chay(['ma_dich_vu' => 'XN1', 'gia_tri' => '5.2', 'ma_chi_so' => '', 'ten_chi_so' => '']);

        $this->assertSame(1, count(array_keys($codes, 'XML4_CHI_SO_EMPTY')));
        $this->assertNotContains('XML4_MA_CHI_SO_EMPTY', $codes);
        $this->assertNotContains('XML4_TEN_CHI_SO_EMPTY', $codes);
        $this->assertSame(['Dòng có giá trị/đơn vị đo nhưng thiếu mã chỉ số và tên chỉ số. Dịch vụ: XN1'],
            $this->moTa(['ma_dich_vu' => 'XN1', 'gia_tri' => '5.2', 'ma_chi_so' => '', 'ten_chi_so' => ''], 'XML4_CHI_SO_EMPTY'));
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
        $this->assertNotContains('XML4_CHI_SO_EMPTY', $codes);
        $this->assertNotContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }

    /** @test */
    public function gia_tri_zero_khong_bao_thieu_chi_so()
    {
        $codes = $this->chay(['ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose', 'gia_tri' => '0', 'mo_ta' => '', 'ket_luan' => '']);
        $this->assertNotContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }
}
