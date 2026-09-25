<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Mã lỗi mới chưa có trong danh mục bị MẶC ĐỊNH là NGHIÊM TRỌNG - migration phải đưa mã
 * BED_DAYS_SPECIAL_NOT_CLAIMED vào ở mức CẢNH BÁO, chạy lại không nhân đôi, và không ghi đè
 * mức lỗi người dùng đã chỉnh.
 */
class MigrationMaLoiNgayGiuongDacBietTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XMLComplete_BED_DAYS_SPECIAL_NOT_CLAIMED';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_09_25_090000_them_ma_loi_ngay_giuong_dac_biet_chua_khai.php',
        ]);
    }

    /** @test */
    public function nap_o_muc_canh_bao()
    {
        $r = DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->first();

        $this->assertNotNull($r);
        $this->assertSame('XMLComplete', $r->xml);
        $this->assertEquals(0, $r->critical_error);
        $this->assertEquals(1, $r->is_check);
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_va_khong_ghi_de_muc_da_chinh()
    {
        DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->update(['critical_error' => true]);

        (new \ThemMaLoiNgayGiuongDacBietChuaKhai())->up();

        $this->assertSame(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->count());
        $this->assertEquals(1, DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->value('critical_error'));
    }
}
