<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocDuLieuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
    }

    /** @test */
    public function tao_dong_va_ep_kieu()
    {
        $m = Xml3176VpkdDvktBatBuoc::create([
            'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => '897', 'so_ho_so_co_kem' => '885',
            'ty_le' => '0.9866', 'nguon' => 'HOC',
        ])->fresh();

        $this->assertTrue($m->hieu_luc);
        $this->assertSame(897, $m->so_ho_so);
        $this->assertSame(0.9866, $m->ty_le);
    }

    /** @test */
    public function ma_dvkt_duy_nhat()
    {
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'HOC', 'so_ho_so' => 1, 'so_ho_so_co_kem' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'TAY', 'so_ho_so' => 0, 'so_ho_so_co_kem' => 0]);
    }

    /** @test */
    public function migration_nap_ma_loi_canh_bao()
    {
        $c = Xml3176ErrorCatalog::where('error_code', 'XMLComplete_DVKT_THIEU_VAT_PHAM_KEM')->first();

        $this->assertNotNull($c);
        $this->assertSame('XMLComplete', $c->xml);
        $this->assertFalse((bool) $c->critical_error);
        $this->assertTrue((bool) $c->is_check);
    }

    /** @test */
    public function config_co_nguong_dvkt_bat_buoc()
    {
        $this->assertEquals(0.9, config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_nguong'));
        $this->assertEquals(20, config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_toi_thieu'));
    }
}
