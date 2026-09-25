<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ma loi moi chua co trong danh muc bi getCriticalErrorStatus() MAC DINH la nghiem trong -
 * migration phai nap ma nay (nguoi dung chot muc NGHIEM TRONG), chay lai khong nhan doi va
 * khong ghi de muc nguoi van hanh da chinh.
 */
class MigrationMaLoiDoiTuongSoSinhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML1_DOI_TUONG_KCB_KHONG_PHAI_SO_SINH';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_09_25_110000_nap_ma_loi_doi_tuong_kcb_so_sinh.php',
        ]);
    }

    /** @test */
    public function nap_o_muc_nghiem_trong()
    {
        $r = DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->first();

        $this->assertNotNull($r);
        $this->assertSame('XML1', $r->xml);
        $this->assertEquals(1, $r->critical_error);
        $this->assertEquals(1, $r->is_check);
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_va_khong_ghi_de_muc_da_chinh()
    {
        DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->update(['critical_error' => false]);

        (new \NapMaLoiDoiTuongKcbSoSinh())->up();

        $this->assertSame(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->count());
        $this->assertEquals(0, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->value('critical_error'));
    }
}
