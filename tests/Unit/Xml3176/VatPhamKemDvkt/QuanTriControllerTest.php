<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Http\Controllers\Category\VatPhamKemDvktController;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Route;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriControllerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const ROUTES = ['index', 'datatable', 'ung-vien-datatable', 'hieu-luc', 'ghi-chu', 'xoa', 'kiem-khop',
        'tim-vat-pham', 'tim-dvkt', 'them', 'ung-vien-them', 'hoc-lai', 'trang-thai', 'xuat', 'nhap', 'nhap-loi'];

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
        ]);
        $u = new User();
        $u->id = 7;
        Auth::setUser($u);
    }

    private function c(): VatPhamKemDvktController
    {
        return $this->app->make(VatPhamKemDvktController::class);
    }

    private function dong(array $f = []): Xml3176VatPhamKemDvkt
    {
        return Xml3176VatPhamKemDvkt::create($f + [
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '', 'ma_dvkt' => '18.0150.0041', 'nguon' => 'HOC',
        ]);
    }

    /** @test */
    public function moi_route_co_auth_va_category_manager()
    {
        foreach (self::ROUTES as $ten) {
            $r = Route::getRoutes()->getByName('category-bhyt.vat-pham-kem-dvkt.' . $ten);
            $this->assertNotNull($r, "Thieu route $ten");
            $mw = $r->gatherMiddleware();
            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
            $this->assertContains('checkrole:category-manager', $mw, "Route $ten sai quyen");
        }
    }

    /** @test */
    public function hieu_luc_tat_thieu_ly_do_422_co_ly_do_ghi_vet()
    {
        $m = $this->dong();

        $r = $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0]), $m->id);
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('Cần ghi lý do khi tắt', $r->getData()->message);

        $r = $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0, 'ghi_chu' => 'sai']), $m->id);
        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame(7, $m->fresh()->nguoi_sua_id);
        $this->assertFalse($m->fresh()->hieu_luc);
    }

    /** @test */
    public function id_khong_co_tra_404()
    {
        $this->assertSame(404, $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 1]), 999)->getStatusCode());
        $this->assertSame(404, $this->c()->ghiChu(Request::create('/', 'POST', []), 999)->getStatusCode());
        $this->assertSame(404, $this->c()->xoa(999)->getStatusCode());
    }

    /** @test */
    public function xoa_dong_hoc_422_dong_tay_ok()
    {
        $this->assertSame(422, $this->c()->xoa($this->dong()->id)->getStatusCode());
        $this->assertSame(200, $this->c()->xoa($this->dong(['ma_dvkt' => 'X', 'nguon' => 'TAY'])->id)->getStatusCode());
    }

    /** @test */
    public function them_tra_thong_diep_va_bao_da_co()
    {
        $this->dong();
        $r = $this->c()->them(Request::create('/', 'POST', [
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ma_dvkt' => ['18.0150.0041', '18.0156.0041'],
        ]));

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('Đã thêm 1 dòng. Đã có: 18.0150.0041', $r->getData()->message);
    }

    /** @test */
    public function ung_vien_them_chi_nhan_dvkt_cua_ung_vien()
    {
        $uv = Xml3176VpkdUngVien::create([
            'lan_hoc_id' => 3, 'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ten_khop' => '',
            'so_dong' => 10, 'so_lien_ket' => 6, 'ty_le_gan' => 0.6,
            'dvkt' => [['ma' => '18.0150.0041', 'ten' => 'CLVT có cản quang', 'so_lan' => 6]],
        ]);

        $r = $this->c()->ungVienThem(Request::create('/', 'POST', ['ma_dvkt' => ['18.0150.0041', '99.9999.9999']]), $uv->id);
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('DVKT không thuộc ứng viên: 99.9999.9999', $r->getData()->message);

        $r = $this->c()->ungVienThem(Request::create('/', 'POST', ['ma_dvkt' => ['18.0150.0041']]), $uv->id);
        $this->assertSame(200, $r->getStatusCode());
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('CLVT có cản quang', $m->ten_dvkt);
        $this->assertSame('Duyệt từ ứng viên lượt #3', $m->ghi_chu);
    }

    /** @test */
    public function hoc_lai_va_trang_thai_tra_mo_ta()
    {
        Queue::fake();

        $d = $this->c()->hocLai()->getData(true);
        $this->assertTrue($d['moi']);
        $this->assertTrue($d['lan_hoc']['dang_chay']);

        $d2 = $this->c()->hocLai()->getData(true);
        $this->assertFalse($d2['moi']);

        $t = $this->c()->trangThai()->getData(true);
        $this->assertSame('CHO', $t['lan_hoc']['trang_thai']);
    }

    /** @test */
    public function kiem_khop_loai_sai_422()
    {
        $r = $this->c()->kiemKhop(Request::create('/', 'GET', ['loai' => 'X', 'ma' => 'a']));
        $this->assertSame(422, $r->getStatusCode());
    }
}
