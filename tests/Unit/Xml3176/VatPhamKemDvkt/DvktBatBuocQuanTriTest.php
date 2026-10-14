<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Http\Controllers\Category\VatPhamKemDvktController;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Route;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocQuanTriTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2014_10_12_000000_create_users_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
        $u = new User();
        $u->id = 7;
        Auth::setUser($u);
        Xml3176VatPhamKemDvkt::create(['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '',
            'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'nguon' => 'HOC']);
    }

    private function c(): VatPhamKemDvktController
    {
        return $this->app->make(VatPhamKemDvktController::class);
    }

    /** @test */
    public function route_moi_co_auth_va_category_manager()
    {
        foreach (['bat-buoc-datatable', 'bat-buoc-hieu-luc', 'bat-buoc-ghi-chu', 'bat-buoc-xoa', 'bat-buoc-them'] as $ten) {
            $r = Route::getRoutes()->getByName('category-bhyt.vat-pham-kem-dvkt.' . $ten);
            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertContains('auth', $r->gatherMiddleware());
            $this->assertContains('checkrole:category-manager', $r->gatherMiddleware());
        }
    }

    /** @test */
    public function them_tay_kiem_nhom_trung_va_chuan_hoa()
    {
        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '99.9999.9999']));
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('DVKT chưa là DVKT mẹ của vật phẩm nào trong bảng tra — không có gì để kiểm', $r->getData()->message);

        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '18.0219.0041_GT', 'ghi_chu' => 'duyệt']));
        $this->assertSame(200, $r->getStatusCode());
        $m = Xml3176VpkdDvktBatBuoc::first();
        $this->assertSame('18.0219.0041', $m->ma_dvkt);
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('CLVT', $m->ten_dvkt);
        $this->assertSame(7, $m->nguoi_sua_id);

        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '18.0219.0041']));
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('Đã có', $r->getData()->message);
    }

    /** @test */
    public function bat_tat_ghi_chu_xoa_dung_chung_luat_bang_tra()
    {
        $hoc = Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'HOC']);

        $this->assertSame(422, $this->c()->batBuocHieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0]), $hoc->id)->getStatusCode());
        $this->assertSame(200, $this->c()->batBuocHieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0, 'ghi_chu' => 'sai']), $hoc->id)->getStatusCode());
        $this->assertFalse($hoc->fresh()->hieu_luc);
        $this->assertSame(7, $hoc->fresh()->nguoi_sua_id);

        $this->assertSame(200, $this->c()->batBuocGhiChu(Request::create('/', 'POST', ['ghi_chu' => '']), $hoc->id)->getStatusCode());
        $this->assertNull($hoc->fresh()->ghi_chu);

        $this->assertSame(422, $this->c()->batBuocXoa($hoc->id)->getStatusCode());
        $this->assertSame(404, $this->c()->batBuocXoa(999)->getStatusCode());
        $tay = Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0220.0041', 'nguon' => 'TAY']);
        $this->assertSame(200, $this->c()->batBuocXoa($tay->id)->getStatusCode());
    }

    /** @test */
    public function datatable_tra_du_lieu()
    {
        DB::table('users')->insert(['id' => 7, 'name' => 'Nguyen Van A', 'email' => 'a@b.c', 'password' => 'x']);
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => 897,
            'so_ho_so_co_kem' => 885, 'ty_le' => 0.9866, 'nguon' => 'HOC', 'nguoi_sua_id' => 7, 'sua_luc' => '2026-10-14 10:00:00']);
        $cot = [];
        foreach (['ma_dvkt', 'ten_dvkt', 'so_ho_so', 'so_ho_so_co_kem', 'ty_le', 'nguon', 'hieu_luc', 'ghi_chu', 'nguoi_sua_luc', 'thao_tac'] as $i => $c) {
            $cot[] = ['data' => $c, 'name' => in_array($c, ['nguoi_sua_luc', 'thao_tac'], true) ? '' : 'xml3176_vpkd_dvkt_bat_buoc.' . $c,
                'searchable' => in_array($c, ['ma_dvkt', 'ten_dvkt'], true) ? 'true' : 'false', 'orderable' => $c === 'thao_tac' ? 'false' : 'true',
                'search' => ['value' => '', 'regex' => 'false']];
        }
        $r = $this->c()->batBuocDatatable(Request::create('/', 'GET', ['draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => $cot, 'order' => [['column' => 0, 'dir' => 'asc']], 'search' => ['value' => '18.0219', 'regex' => 'false']]));
        $d = $r->getData(true);

        $this->assertSame(1, $d['recordsFiltered']);
        $this->assertContains('vpkd-bb-hieu-luc', $d['data'][0]['hieu_luc']);
        $this->assertContains('Nguyen Van A', $d['data'][0]['nguoi_sua_luc']);
        $this->assertContains('98,7%', $d['data'][0]['ty_le']);
    }
}
