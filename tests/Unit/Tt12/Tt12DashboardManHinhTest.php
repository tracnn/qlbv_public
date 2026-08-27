<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Cache;
use App\Services\BHYT\DanhSachCoSo;

/**
 * Chot canh cho man dashboard do phu: route, menu, va nguyen tac khong tu viet SQL.
 */
class Tt12DashboardManHinhTest extends TestCase
{
    use DungBangTt12Sqlite;

    /**
     * Chi test controller_qua_container_... thuc su dung DB/cache, nhung dung chung setUp
     * cho ca lop la vo hai: cac test con lai chi doc file nguon, khong dung DB.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Cache::put(DanhSachCoSo::KHOA_CACHE, array(
            '01929' => '01929 - Bach Mai',
        ), 60);
    }

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
    public function ca_hai_route_dashboard_deu_nam_trong_chot_quyen_xml_man()
    {
        // Cung quyen voi hai man TT12 kia. Thieu chot nay thi bat ky tai khoan dang nhap nao
        // cung doc duoc do phu danh muc cua don vi. bhyt.tt12.dashboard.do-phu la duong tra
        // TOAN BO du lieu, dang o dung nhom hien tai nhung khong co chot rieng nao giu no lai
        // neu sau nay ai do tach route ra ngoai nhom 'bhyt/' - test nay canh chinh dieu do
        // bang cach kiem TUNG route mot, khong chi mot trong hai.
        foreach (array('bhyt.tt12.dashboard', 'bhyt.tt12.dashboard.do-phu') as $tenRoute) {
            $duong = ltrim(parse_url(route($tenRoute), PHP_URL_PATH), '/');
            $timThay = false;

            foreach (app('router')->getRoutes() as $r) {
                if ($r->uri() === $duong) {
                    $timThay = true;
                    $this->assertContains('checkrole:xml-man', $r->gatherMiddleware(),
                        $tenRoute . ' phai nam trong chot quyen checkrole:xml-man');

                    break;
                }
            }

            $this->assertTrue($timThay, 'khong tim thay route ' . $duong);
        }
    }

    /** @test */
    public function controller_qua_container_tra_ve_du_hai_khoa_luoi_va_dang_do_dang()
    {
        // Moi test khac goi thang new Tt12DashboardService() - khong test nao di qua
        // container cua Laravel. Du an nay da ba lan dinh bay tiem phu thuoc cua container
        // tren PHP 7.4/Laravel 5.5 (xem memory bay-tiem-container-handle), nen mot test di
        // qua app() la dang co de bat truong hop constructor cua controller khong duoc
        // container giai quyet dung.
        $controller = app(\App\Http\Controllers\Dashboard\Tt12DashboardController::class);

        $phanHoi = $controller->doPhu();
        $kq = json_decode($phanHoi->getContent(), true);

        $this->assertArrayHasKey('luoi', $kq);
        $this->assertArrayHasKey('dang_do_dang', $kq);
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
        // Canh bao nay gio phai lay tu CHINH phan hoi cua bhyt.tt12.dashboard.do-phu
        // (kq.doc_duoc_his), khong con la mot lan goi rieng DanhSachCoSo::danhSach() luc
        // render trang: hai lan goi doc lap co the roi vao hai phia cua mot lan cache 60
        // phut het han, sinh ra hai cau tra loi khac nhau cho cung mot cau hoi trong cung
        // mot lan tai trang - xem Tt12DashboardService::doPhu() va
        // his_hong_khong_duoc_danh_dau_ngoai_danh_sach_cho_bat_ky_co_so_nao().
        //
        // Khang dinh CA HAI: container canh bao ton tai (rong, an san) VA script doc
        // kq.doc_duoc_his de bat/tat no - thieu mot trong hai la mat het y nghia cua canh
        // bao.
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains('id="canh-bao-his"', $noiDung,
            'Thieu container canh bao HIS trong view');
        $this->assertContains('style="display:none;"', $noiDung,
            'Container canh bao HIS phai an san, chi JS moi duoc mo no');
        $this->assertContains('Không đọc được danh sách cơ sở', $noiDung,
            'Thieu noi dung canh bao HIS trong view');

        $this->assertContains("kq.doc_duoc_his", $noiDung,
            'Script phai doc kq.doc_duoc_his tu phan hoi cua route do-phu de quyet dinh bat/tat canh bao');
    }

    /** @test */
    public function man_danh_sach_doc_bo_loc_tu_query_string()
    {
        // Dac ta muc 5.3: bam mot o tren luoi phai mo man Danh sach da LOC SAN theo mau va
        // co so. Dashboard sinh dung link '...index?mau=...&ma_cskcb=...' (va
        // '...?trang_thai=...' cho dai do dang), nhung neu man danh sach khong doc lai cac
        // tham so nay tu URL thi bo loc chi song trong thanh dia chi, khong he tac dong len
        // du lieu hien thi - nguoi dung bam vao o "Đã tiếp nhận" van thay mot bang RONG.
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertContains('URLSearchParams', $noiDung,
            'View danh sach phai doc query string de ap bo loc tu dashboard');

        foreach (array('mau', 'ma_cskcb', 'trang_thai') as $thamSo) {
            $this->assertContains("'" . $thamSo . "'", $noiDung,
                'Thieu doc tham so "' . $thamSo . '" tu query string');
        }

        // Van de khoang ngay: man danh sach loc theo imported_at, con o luoi tren dashboard
        // hien thi thoi_gian_tiep_nhan - hai moc thoi gian khac nhau. Neu view khong doc
        // ca tu_ngay/den_ngay tu URL thi ket qua van co the RONG du da loc dung mau/co so,
        // vi bo loc ngay mac dinh cua partials.date_range la "Hom nay".
        $this->assertContains('tu_ngay', $noiDung,
            'View danh sach phai doc tu_ngay tu query string, khong chi mau/ma_cskcb/trang_thai');
        $this->assertContains('den_ngay', $noiDung,
            'View danh sach phai doc den_ngay tu query string, khong chi mau/ma_cskcb/trang_thai');
    }

    /** @test */
    public function view_nap_du_lieu_qua_route_do_phu()
    {
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains("route('bhyt.tt12.dashboard.do-phu')", $noiDung);
    }
}
