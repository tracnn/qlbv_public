<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class MigrationMaLoiThoiGianPtttDaiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML3_EXECUTION_TIME_TOO_LONG';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_120000_nap_ma_loi_thoi_gian_pttt_dai.php',
        ]);
    }

    /** @test */
    public function nap_o_muc_nghiem_trong()
    {
        $r = DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->first();

        $this->assertNotNull($r);
        $this->assertSame('XML3', $r->xml);
        $this->assertEquals(1, $r->critical_error);
        $this->assertEquals(1, $r->is_check);
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_va_khong_ghi_de_muc_da_chinh()
    {
        DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->update(['critical_error' => false]);

        (new \NapMaLoiThoiGianPtttDai())->up();

        $this->assertSame(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->count());
        $this->assertEquals(0, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->value('critical_error'));
    }
}
