<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Services\Xml3176\Xml3176BoDem;
use App\Services\Xml3176\Xml3176PhienBanDanhMuc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Task 8: phien ban danh muc - sua danh muc co hieu luc ngay o job ke tiep trong worker.
 */
class PhienBanDanhMucTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MIGRATION = '2026_10_15_130000_create_xml3176_phien_ban_danh_muc_table.php';

    private $dem = 0;

    private function coBang()
    {
        $this->bootXml3176Sqlite([self::MIGRATION]);
    }

    private function khongBang()
    {
        $this->bootXml3176Sqlite([]);
    }

    private function phienBan()
    {
        return (int) DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->value('phien_ban');
    }

    /** Nho mot muc; tra so lan ham tinh duoc goi tu dau test. */
    private function nho()
    {
        Xml3176BoDem::nho('t', 'k', function () {
            $this->dem++;

            return 'v';
        });

        return $this->dem;
    }

    /** @test */
    public function migration_tao_bang_va_dong_1_va_chay_lai_khong_loi()
    {
        $this->coBang();
        $this->assertTrue(Schema::hasTable('xml3176_phien_ban_danh_muc'));
        $this->assertSame(0, $this->phienBan());

        DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->update(['phien_ban' => 7]);
        (new \CreateXml3176PhienBanDanhMucTable())->up();

        $this->assertSame(1, DB::table('xml3176_phien_ban_danh_muc')->count());
        $this->assertSame(7, $this->phienBan(), 'chay lai khong dat lai so phien ban');
    }

    /** @test */
    public function tang_cong_mot_va_ghi_ly_do()
    {
        $this->coBang();
        Xml3176PhienBanDanhMuc::tang('nhap danh muc');
        Xml3176PhienBanDanhMuc::tang('xoa danh muc');

        $dong = DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->first();
        $this->assertSame(2, (int) $dong->phien_ban);
        $this->assertSame('xoa danh muc', $dong->ly_do);
        $this->assertNotNull($dong->updated_at);
    }

    /** @test */
    public function tang_xoa_bo_dem_cua_chinh_tien_trinh()
    {
        $this->coBang();
        $this->nho();
        Xml3176PhienBanDanhMuc::tang('x');
        $this->assertSame(2, $this->nho());
    }

    /** @test */
    public function tang_khi_chua_co_bang_khong_nem()
    {
        $this->khongBang();
        Xml3176PhienBanDanhMuc::tang('x');
        $this->assertFalse(Schema::hasTable('xml3176_phien_ban_danh_muc'));
    }

    /** @test */
    public function dong_bo_xoa_bo_dem_khi_tien_trinh_khac_tang_phien_ban()
    {
        $this->coBang();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->assertSame(1, $this->nho());

        // Tien trinh khac (web) sua danh muc: chi thay doi dong trong CSDL.
        DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->update(['phien_ban' => 5]);

        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->assertSame(2, $this->nho(), 'phien ban doi -> bo dem bi xoa, tinh lai');
    }

    /** @test */
    public function dong_bo_khong_xoa_khi_phien_ban_khong_doi_va_chi_mot_truy_van()
    {
        $this->coBang();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->nho();

        DB::enableQueryLog();
        DB::flushQueryLog();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $soTruyVan = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(2, $soTruyVan, 'moi lan dong bo dung mot truy van');
        $this->assertSame(1, $this->nho(), 'phien ban khong doi -> giu bo dem');
    }

    /** @test */
    public function dong_bo_bat_buoc_thi_xoa_du_phien_ban_khong_doi()
    {
        $this->coBang();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->nho();
        Xml3176PhienBanDanhMuc::dongBoBoDem(true);
        $this->assertSame(2, $this->nho());
    }

    /** @test */
    public function dong_bo_khi_chua_co_bang_khong_nem_khong_xoa()
    {
        $this->khongBang();
        $this->nho();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->assertSame(1, $this->nho());
    }

    /** @test */
    public function quen_phien_ban_thi_lan_dong_bo_sau_coi_nhu_tien_trinh_moi()
    {
        $this->coBang();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->nho();
        Xml3176PhienBanDanhMuc::quenPhienBan();
        Xml3176PhienBanDanhMuc::dongBoBoDem();
        $this->assertSame(2, $this->nho(), 'lan dau trong tien trinh xoa bo dem (vo hai: bo dem rong)');
    }
}
