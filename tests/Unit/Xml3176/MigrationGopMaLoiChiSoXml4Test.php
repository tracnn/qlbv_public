<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class MigrationGopMaLoiChiSoXml4Test extends TestCase
{
    use Xml3176RuleTestSupport;

    const MOI = 'XML4_CHI_SO_EMPTY';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_161708_create_xml3176_error_catalogs_table.php']);
        require_once database_path('migrations/2026_10_15_130000_gop_ma_loi_chi_so_xml4.php');
    }

    private function nap(string $ma, bool $chan, string $moTa = 'cu'): void
    {
        DB::table('xml3176_error_catalogs')->insert([
            'xml' => 'XML4', 'error_code' => $ma, 'error_name' => $ma, 'description' => $moTa,
            'critical_error' => $chan, 'is_check' => true,
        ]);
    }

    private function dong(string $ma)
    {
        return DB::table('xml3176_error_catalogs')->where('error_code', $ma)->first();
    }

    /** @test */
    public function ma_moi_lay_muc_cua_ma_cu_va_tat_hai_ma_cu()
    {
        $this->nap('XML4_MA_CHI_SO_EMPTY', true);
        $this->nap('XML4_TEN_CHI_SO_EMPTY', true);

        (new \GopMaLoiChiSoXml4())->up();

        $this->assertEquals(1, $this->dong(self::MOI)->critical_error);
        $this->assertEquals(1, $this->dong(self::MOI)->is_check);
        $this->assertEquals(0, $this->dong('XML4_MA_CHI_SO_EMPTY')->is_check);
        $this->assertEquals(0, $this->dong('XML4_TEN_CHI_SO_EMPTY')->is_check);
        $this->assertSame('cu — ' . \GopMaLoiChiSoXml4::GHI_CHU, $this->dong('XML4_TEN_CHI_SO_EMPTY')->description);
    }

    /** @test */
    public function ma_moi_da_tu_tao_o_muc_nghiem_trong_bi_ep_ve_muc_ma_cu()
    {
        $this->nap('XML4_MA_CHI_SO_EMPTY', false);
        $this->nap(self::MOI, true);

        (new \GopMaLoiChiSoXml4())->up();
        (new \GopMaLoiChiSoXml4())->up();

        $this->assertSame(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MOI)->count());
        $this->assertEquals(0, $this->dong(self::MOI)->critical_error);
        $this->assertSame('cu — ' . \GopMaLoiChiSoXml4::GHI_CHU, $this->dong('XML4_MA_CHI_SO_EMPTY')->description, 'chay lai khong noi ghi chu hai lan');
    }

    /** @test */
    public function chua_co_ma_cu_thi_canh_bao()
    {
        (new \GopMaLoiChiSoXml4())->up();

        $this->assertEquals(0, $this->dong(self::MOI)->critical_error);
    }
}
