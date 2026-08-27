<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

/**
 * Chot canh cho man dashboard do phu: route, menu, va nguyen tac khong tu viet SQL.
 */
class Tt12DashboardManHinhTest extends TestCase
{
    /** @test */
    public function hai_route_dashboard_duoc_khai_bao()
    {
        $this->assertSame(
            route('bhyt.tt12.dashboard') . '/do-phu',
            route('bhyt.tt12.dashboard.do-phu'),
            'route do-phu phai la duong dan dashboard cong "/do-phu"'
        );
    }

    /** @test */
    public function route_dashboard_nam_trong_chot_quyen_xml_man()
    {
        // Cung quyen voi hai man TT12 kia. Thieu chot nay thi bat ky tai khoan dang nhap nao
        // cung doc duoc do phu danh muc cua don vi.
        $duong = parse_url(route('bhyt.tt12.dashboard'), PHP_URL_PATH);
        $duong = ltrim($duong, '/');

        foreach (app('router')->getRoutes() as $r) {
            if ($r->uri() === $duong) {
                $this->assertContains('checkrole:xml-man', $r->gatherMiddleware());

                return;
            }
        }

        $this->fail('khong tim thay route ' . $duong);
    }

    /** @test */
    public function menu_co_muc_dashboard_trong_nhom_TT12()
    {
        // Man hinh khong co loi vao tren menu la man hinh khong ai dung.
        $noiDung = file_get_contents(base_path('config/adminlte.php'));

        $this->assertContains("'route'  => 'bhyt.tt12.dashboard'", $noiDung);
    }

    /** @test */
    public function service_KHONG_tu_viet_SQL_dem_trang_thai()
    {
        // Dem theo trang thai phai goi Tt12DanhSach::truyVan(). Viet ban SQL thu hai o day la
        // tao ra mot man hinh bao so KHAC voi chinh bo loc ngay ben canh no, va nguoi dung se
        // thoi tin ca hai.
        $nguon = file_get_contents(
            base_path('app/Services/Dashboard/Tt12DashboardService.php')
        );

        foreach (array("where('ma_ket_qua'", "where('is_signed'", "where('checked_at'",
                       "where('so_loi'", 'whereNotNull') as $cam) {
            $this->assertNotContains($cam, $nguon,
                'Tt12DashboardService tu viet luat trang thai (' . $cam
                . ') thay vi goi Tt12DanhSach::truyVan()');
        }
    }

    /** @test */
    public function view_bien_dich_duoc()
    {
        $duongDan = resource_path('views/dashboard/tt12.blade.php');

        $this->assertFileExists($duongDan);

        $php = app('blade.compiler')->compileString(file_get_contents($duongDan));

        $tam = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($tam, $php);

        exec('php -l ' . escapeshellarg($tam), $ra, $ma);
        unlink($tam);

        $this->assertSame(0, $ma, implode(PHP_EOL, $ra));
    }

    /** @test */
    public function view_bao_ro_khi_khong_doc_duoc_danh_sach_co_so()
    {
        // DanhSachCoSo tra mang RONG khi HIS hong. Hien mot luoi trong nhu the moi thu chua
        // khai la noi doi: "khong biet" va "chua khai" la hai chuyen khac han nhau ma cung
        // trong giong nhau.
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains('Không đọc được danh sách cơ sở', $noiDung);
    }

    /** @test */
    public function view_nap_du_lieu_qua_route_do_phu()
    {
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains("route('bhyt.tt12.dashboard.do-phu')", $noiDung);
    }
}
