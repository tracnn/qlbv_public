<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Services\Dashboard\CtdtDashboardService;

/**
 * Cot "So loi" bang 0 khi worker JobCtdt khong chay trong Y HET nhu moi ho so deu sach.
 * Man hinh nay la thu duy nhat bat duoc chuyen do - nen chinh no khong duoc noi doi.
 */
class CtdtDashboardTest extends TestCase
{
    // KHONG DatabaseMigrations: trait do goi migrate:fresh, tuc DROP toan bo bang cua CSDL
    // phat trien. Da xay ra that ngay 2026-08-21.
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSo(array $ghiDe = [])
    {
        return CtdtHoSo::create(array_merge([
            'ma_ho_so'    => 'YT' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'dich_vu'     => 'CT2025',
            'loai_hs'     => '39',
            'macskcb'     => '01001',
            'imported_at' => '2026-08-20 08:00:00',
            'so_loi'      => 0,
        ], $ghiDe));
    }

    /** @test */
    public function liet_ke_DU_CHIN_trang_thai_ke_ca_cai_bang_khong()
    {
        // Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc.
        // "Ky so that bai: 0" la mot thong tin; mot cot vang la mot cau hoi.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(
            count(CtdtTrangThaiGui::NHAN),
            $kq['theo_trang_thai'],
            'Phai liet ke du moi trang thai, ke ca cai dang bang 0'
        );
    }

    /** @test */
    public function dem_theo_trang_thai_khop_voi_CtdtTrangThaiGui()
    {
        // Man hinh bao mot dang, bo loc ngay ben canh bao mot neo la nguoi dung thoi tin ca
        // hai. Day la ly do khong duoc viet ban SQL thu ba cho luat trang thai.
        $this->hoSo(['checked_at' => null]);                                   // CHUA_KIEM
        $this->hoSo(['checked_at' => '2026-08-20 08:00:00', 'so_loi' => 3]);   // CON_LOI

        $kq = (new CtdtDashboardService())->sucKhoe([]);
        $dem = [];

        foreach ($kq['theo_trang_thai'] as $dong) {
            $dem[$dong['ma']] = $dong['so_luong'];
        }

        $this->assertSame(1, $dem[CtdtTrangThaiGui::CHUA_KIEM]);
        $this->assertSame(1, $dem[CtdtTrangThaiGui::CON_LOI]);
    }

    /** @test */
    public function nhan_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['theo_trang_thai'] as $dong) {
            $this->assertSame(
                CtdtTrangThaiGui::nhan($dong['ma']),
                $dong['nhan'],
                'Nhan phai lay tu CtdtTrangThaiGui, khong duoc go lai'
            );
        }
    }

    /** @test */
    public function liet_ke_du_ba_hang_doi()
    {
        // Thieu worker nao thi moi ho so dung khung o buoc do. Ba hang doi deu BAT BUOC, nen
        // ca ba deu phai co mat tren man hinh - ke ca khi khong dem duoc.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(3, $kq['hang_doi']);
    }

    /** @test */
    public function khong_dem_duoc_hang_doi_thi_tra_null_chu_KHONG_tra_0()
    {
        // Hang doi dung driver khac 'database' thi khong co bang jobs de dem. Tra 0 o day la
        // NOI DOI: nguoi doc se thay "0 job dang cho" va yen tam, trong khi that ra man hinh
        // khong biet gi ca.
        config(['queue.default' => 'sync']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['hang_doi'] as $hd) {
            $this->assertNull($hd['so_job'],
                'Khong dem duoc phai tra null, khong duoc tra 0');
        }
    }

    /** @test */
    public function ton_dong_dem_ho_so_chua_len_duoc_cong()
    {
        // "Ho so cu nhat chua gui da nam bao lau" la cau hoi bat duoc mot hang doi chet
        // cham - thu ma bieu do so luong khong bao gio chi ra.
        $this->hoSo(['checked_at' => null, 'imported_at' => '2026-08-01 08:00:00']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertSame(1, $kq['ton_dong']['so_ho_so']);
        $this->assertNotNull($kq['ton_dong']['cu_nhat_ngay']);
    }

    /** @test */
    public function blade_dashboard_bien_dich_duoc()
    {
        // Trang blade chi vo khi co nguoi MO no. detail.blade.php da chet suot tu Giai doan
        // 2B vi mot @if lot vao trong chu thich JavaScript, va khong test nao bat duoc.
        $noiDung = file_get_contents(resource_path('views/dashboard/ctdt.blade.php'));

        \Illuminate\Support\Facades\Blade::compileString($noiDung);

        $this->assertTrue(true, 'compileString() nem thi test do o dong tren');
    }
}
