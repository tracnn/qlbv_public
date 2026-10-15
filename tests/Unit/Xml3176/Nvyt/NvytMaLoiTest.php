<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176\NvytRule;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class NvytMaLoiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_161708_create_xml3176_error_catalogs_table.php']);
    }

    private function chay(): void
    {
        require_once database_path('migrations/2026_10_15_100000_gop_ma_loi_nvyt_xml3176.php');
        (new \GopMaLoiNvytXml3176())->up();
    }

    /** @test */
    public function ba_ma_moi_dung_muc_chan()
    {
        $this->chay();
        $mong = [
            'XMLComplete_NVYT_THIEU' => [true, 'Thiếu mã nhân viên y tế'],
            'XMLComplete_NVYT_KHONG_CO_TRONG_DM' => [false, 'Mã nhân viên y tế không có trong danh mục NVYT'],
            'XMLComplete_NVYT_SAI_DINH_DANG' => [false, 'Mã nhân viên y tế sai định danh'],
        ];
        $this->assertSame(3, Xml3176ErrorCatalog::where('xml', 'XMLComplete')->count());
        foreach ($mong as $ma => $m) {
            $r = Xml3176ErrorCatalog::where('error_code', $ma)->first();
            $this->assertNotNull($r, $ma);
            $this->assertSame('XMLComplete', $r->xml);
            $this->assertSame($m[0], (bool) $r->critical_error, $ma);
            $this->assertSame($m[1], $r->error_name, $ma);
            $this->assertTrue((bool) $r->is_check, $ma);
            $this->assertNotEmpty($r->description, $ma);
        }
    }

    /** @test */
    public function chay_hai_lan_khong_nhan_doi_va_giu_muc_da_chinh()
    {
        $this->chay();
        Xml3176ErrorCatalog::where('error_code', 'XMLComplete_NVYT_THIEU')->update(['critical_error' => false]);
        $this->chay();
        $this->assertSame(1, Xml3176ErrorCatalog::where('error_code', 'XMLComplete_NVYT_THIEU')->count());
        $this->assertFalse((bool) Xml3176ErrorCatalog::where('error_code', 'XMLComplete_NVYT_THIEU')->first()->critical_error);
    }

    /** @test */
    public function ma_cu_bi_tat()
    {
        $cu = NvytRule::MA_CU;
        foreach ([$cu[0], $cu[5]] as $ma) {
            Xml3176ErrorCatalog::create(['xml' => 'XMLX', 'error_code' => $ma, 'error_name' => 'x', 'critical_error' => true, 'is_check' => true]);
        }
        Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => 'XML1_KHAC', 'error_name' => 'x', 'critical_error' => true, 'is_check' => true]);

        $this->chay();

        $this->assertFalse((bool) Xml3176ErrorCatalog::where('error_code', $cu[0])->first()->is_check);
        $this->assertFalse((bool) Xml3176ErrorCatalog::where('error_code', $cu[5])->first()->is_check);
        $this->assertTrue((bool) Xml3176ErrorCatalog::where('error_code', 'XML1_KHAC')->first()->is_check);

        // down(): bat lai ma cu, khong xoa ma moi
        (new \GopMaLoiNvytXml3176())->down();
        $this->assertTrue((bool) Xml3176ErrorCatalog::where('error_code', $cu[0])->first()->is_check);
        $this->assertSame(3, Xml3176ErrorCatalog::where('xml', 'XMLComplete')->count());
    }

    /** @test */
    public function ma_cu_du_34()
    {
        $this->assertCount(34, NvytRule::MA_CU);
        $this->assertCount(34, array_unique(NvytRule::MA_CU));
    }
}
