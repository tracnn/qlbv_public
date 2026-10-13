<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktModelTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php']);
    }

    /** @test */
    public function tao_dong_va_ep_kieu()
    {
        $m = Xml3176VatPhamKemDvkt::create([
            'loai' => Xml3176VatPhamKemDvkt::LOAI_VTYT, 'ma_vat_pham' => 'N08.00.150',
            'ten_vat_pham' => 'Buồng tiêm truyền', 'ten_khop' => 'buồng tiêm truyền',
            'ma_dvkt' => '12.0448.1187', 'ten_dvkt' => 'Đặt buồng tiêm truyền dưới da',
            'nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'so_lan_gan' => '44', 'ty_le_gan' => '0.9362',
        ])->fresh();

        $this->assertTrue($m->hieu_luc);
        $this->assertSame(44, $m->so_lan_gan);
        $this->assertSame(0.9362, $m->ty_le_gan);
    }

    /** @test */
    public function khoa_duy_nhat_chan_trung()
    {
        $d = ['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '', 'ma_dvkt' => '18.0150.0041', 'nguon' => 'HOC'];
        Xml3176VatPhamKemDvkt::create($d);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Xml3176VatPhamKemDvkt::create($d);
    }

    /** @test */
    public function config_co_nguong_mac_dinh()
    {
        $this->assertEquals(0.8, config('xml3176.vat_pham_kem_dvkt.nguong'));
        $this->assertEquals(5, config('xml3176.vat_pham_kem_dvkt.toi_thieu'));
    }
}
