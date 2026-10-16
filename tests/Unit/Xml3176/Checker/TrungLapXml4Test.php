<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176Xml4Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026: dong XML4 thuoc DVKT nhom bat buoc mo ta/ket luan (ma_nhom 2) da
 * bi MO_TA_EMPTY/KET_LUAN_EMPTY bao, thi XN_MISSING_VALUE_RESULT khong bao them (prod: 32
 * dong ra 3 loi cho mot sai sot).
 */
class TrungLapXml4Test extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
        ]);

        // ma_nhom 2 = bat buoc ket luan (config xml3176.xml4.xml3_ma_nhom_require_ket_luan)
        Xml3176Xml3::create(['ma_lk' => 'L1', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ma_nhom' => '2']);
        Xml3176Xml3::create(['ma_lk' => 'L1', 'stt' => 2, 'ma_dich_vu' => 'DV2', 'ma_nhom' => '3']);
    }

    private function codes(array $a): array
    {
        $c = $this->makeChecker(Xml3176Xml4Checker::class);
        $row = Xml3176Xml4::create($a + ['ma_lk' => 'L1', 'stt' => 1]);

        return array_merge(
            $this->errorCodes($this->invokePrivate($c, 'infoChecker', $row)),
            $this->errorCodes($this->invokePrivate($c, 'checkChiSo', $row))
        );
    }

    /** @test */
    public function nhom_bat_buoc_ket_luan_rong_het_chi_bao_quy_tac_cu()
    {
        $codes = $this->codes(['ma_dich_vu' => 'DV1', 'ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose']);

        $this->assertContains('XML4_INFO_ERROR_MO_TA_EMPTY', $codes);
        $this->assertContains('XML4_INFO_ERROR_KET_LUAN_EMPTY', $codes);
        $this->assertNotContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }

    /** @test */
    public function nhom_khong_bat_buoc_ket_luan_van_bao_xn_missing()
    {
        $codes = $this->codes(['ma_dich_vu' => 'DV2', 'ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose']);

        $this->assertContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
        $this->assertNotContains('XML4_INFO_ERROR_MO_TA_EMPTY', $codes);
        $this->assertNotContains('XML4_INFO_ERROR_KET_LUAN_EMPTY', $codes);
    }

    /** @test */
    public function mo_ta_ket_luan_toan_khoang_trang_quy_tac_cu_im_lang_thi_xn_missing_van_bao()
    {
        // empty('  ') = false nen MO_TA/KET_LUAN_EMPTY khong bao; XN_MISSING phai giu loi.
        $codes = $this->codes(['ma_dich_vu' => 'DV1', 'ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose',
            'mo_ta' => '  ', 'ket_luan' => '  ']);

        $this->assertNotContains('XML4_INFO_ERROR_MO_TA_EMPTY', $codes);
        $this->assertNotContains('XML4_INFO_ERROR_KET_LUAN_EMPTY', $codes);
        $this->assertContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }
}
