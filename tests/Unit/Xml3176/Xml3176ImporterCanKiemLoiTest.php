<?php

namespace Tests\Unit\Xml3176;

use App\Services\Xml3176\Xml3176Importer;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Cong chan ra loi: ho so dich vu (MA_DOITUONG_KCB = 9) khong day job ra loi.
 *
 * CAM RefreshDatabase - se xoa sach CSDL dev. Dung sqlite trong bo nho.
 */
class Xml3176ImporterCanKiemLoiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
        config(['xml3176.ma_doituong_kcb_khong_kiem' => ['9']]);
    }

    private function themHoSo($maLk, $maDoiTuong)
    {
        DB::table('xml3176_xml1s')->insert([
            'ma_lk' => $maLk,
            'stt' => 1,
            'ma_doituong_kcb' => $maDoiTuong,
        ]);
    }

    private function canKiem($maLk)
    {
        return $this->invokePrivate(app(Xml3176Importer::class), 'canKiemLoi', $maLk);
    }

    /** @test */
    public function ho_so_dich_vu_thi_khong_ra_loi()
    {
        $this->themHoSo('LK9', '9');
        $this->assertFalse($this->canKiem('LK9'));
    }

    /** @test */
    public function nhanh_con_cua_ma_dich_vu_cung_khong_ra_loi()
    {
        $this->themHoSo('LK91', '9.1');
        $this->assertFalse($this->canKiem('LK91'));
    }

    /** @test */
    public function ho_so_bhyt_van_ra_loi()
    {
        $this->themHoSo('LK11', '1.1');
        $this->assertTrue($this->canKiem('LK11'));
    }

    /** @test */
    public function khong_co_dong_xml1_thi_van_ra_loi()
    {
        // Thieu can cu thi khong tu y bo qua.
        $this->assertTrue($this->canKiem('LK-KHONG-CO'));
    }

    /** @test */
    public function ma_doi_tuong_rong_thi_van_ra_loi()
    {
        $this->themHoSo('LK-RONG', null);
        $this->assertTrue($this->canKiem('LK-RONG'));
    }

    /** @test */
    public function danh_sach_cau_hinh_rong_thi_ra_loi_moi_ho_so()
    {
        config(['xml3176.ma_doituong_kcb_khong_kiem' => []]);
        $this->themHoSo('LK9', '9');
        $this->assertTrue($this->canKiem('LK9'));
    }
}
