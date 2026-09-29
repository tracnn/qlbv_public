<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ma loi moi chua co trong danh muc bi getCriticalErrorStatus() MAC DINH la nghiem trong:
 * quy tac no lan dau se tu ghi dong danh muc o muc nghiem trong va chan xuat 342 ho so.
 * Migration phai nap ma nay o muc CANH BAO, chay lai khong nhan doi, khong ghi de muc nguoi
 * van hanh da chinh.
 */
class MigrationMaLoiKhongTheSaiMaTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML1_DOI_TUONG_KCB_KHONG_THE_SAI_MA';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_09_29_100000_nap_ma_loi_doi_tuong_kcb_khong_the.php',
        ]);
    }

    /** @test */
    public function nap_o_muc_canh_bao_khong_chan_xuat()
    {
        $r = DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->first();

        $this->assertNotNull($r);
        $this->assertSame('XML1', $r->xml);
        $this->assertEquals(0, $r->critical_error);
        $this->assertEquals(1, $r->is_check);
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_va_khong_ghi_de_muc_da_chinh()
    {
        DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->update(['critical_error' => true]);

        (new \NapMaLoiDoiTuongKcbKhongThe())->up();

        $this->assertSame(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->count());
        $this->assertEquals(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->value('critical_error'));
    }

    /** @test */
    public function migration_khong_goi_lai_seeder()
    {
        $src = file_get_contents(database_path('migrations/2026_09_29_100000_nap_ma_loi_doi_tuong_kcb_khong_the.php'));

        $this->assertContains('firstOrCreate', $src);
        $this->assertNotContains('Xml3176ErrorCatalogDoiTuongKcbSeeder', $src,
            'Goi lai seeder (updateOrCreate) se ghi de cau hinh 13 ma cu nguoi van hanh da tu chinh');
    }
}
