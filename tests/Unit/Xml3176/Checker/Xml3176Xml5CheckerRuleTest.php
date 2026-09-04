<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml5;
use App\Services\Xml3176Xml5Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml5CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152843_create_xml3176_xml5s_table.php']);
    }

    /** @test */
    public function bat_dien_bien_trung_o_dong_stt_lon_hon()
    {
        Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 1, 'dien_bien_ls' => 'Bệnh ổn định']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 2, 'dien_bien_ls' => 'bệnh  ổn định']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }

    /** @test */
    public function khong_bat_khi_khac_nhau()
    {
        Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 1, 'dien_bien_ls' => 'Sốt cao']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 2, 'dien_bien_ls' => 'Hết sốt']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertNotContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }
}
