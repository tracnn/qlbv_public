<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtImporter;

/**
 * Chot canh cho man chi tiet dang modal.
 *
 * VAN DE GOC: nut-ky-va-gui.blade.php tung tu day JS bang @push('after-scripts'). Chi thi
 * do CHI co tac dung khi view duoc render ben trong mot layout co @stack. Nap partial ay
 * bang AJAX vao modal thi khoi @push bi bo di KHONG MOT LOI BAO: nut "Ky va gui" van hien,
 * van bam duoc, va khong co gi xay ra.
 *
 * Do la hang loi te nhat trong module nay - im lang, va nam dung tren nut nguy hiem nhat.
 * Cac test duoi day canh cau truc chu khong dua vao ky luat nguoi sua sau.
 */
class CtdtChiTietModalTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    private function napMau()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test', 'CHAN_DOAN' => 'Dau bung',
            ]),
        ]]);

        (new CtdtImporter())->nhapTuChuoi($xml);
    }

    /** @return string noi dung tho cua mot view blade */
    private function nguon($duongDanTuongDoi)
    {
        $duongDan = resource_path('views/bhyt/ctdt/' . $duongDanTuongDoi);

        $this->assertFileExists($duongDan);

        return file_get_contents($duongDan);
    }

    /** @test */
    public function than_chi_tiet_khong_chua_mot_dong_script_nao()
    {
        // Fragment khong co script thi khong co gi de roi. Day la ca chot canh chinh cua
        // task nay: no bien "dung quen @push" tu mot loi dan thanh mot bat bien co the kiem.
        $this->assertNotContains('<script', $this->nguon('partials/than-chi-tiet.blade.php'),
            'than-chi-tiet la fragment nap bang AJAX - script trong do se khong bao gio chay');
    }

    /** @test */
    public function than_chi_tiet_la_fragment_chu_khong_phai_trang()
    {
        $nguon = $this->nguon('partials/than-chi-tiet.blade.php');

        $this->assertNotContains('@extends', $nguon);
        $this->assertNotContains('@section', $nguon);
    }

    /** @test */
    public function nut_ky_va_gui_khong_con_push_after_scripts()
    {
        // Day dung la nguon cua loi. Neu @push quay lai day thi nut se im lang khi o trong
        // modal - va chi lo ra khi co nguoi bam that.
        $this->assertNotContains('@push', $this->nguon('partials/nut-ky-va-gui.blade.php'),
            'nut-ky-va-gui duoc nap bang AJAX vao modal; @push o day roi khong dau vet');
    }

    /** @test */
    public function trang_chi_tiet_dung_lai_hai_partial_chu_khong_chep_lai()
    {
        // Trang rieng va modal PHAI dung chung mot ban than. Hai ban se lech nhau, va khong
        // co dau hieu gi cho toi luc ai do doi chieu tung dong.
        $nguon = $this->nguon('detail.blade.php');

        $this->assertContains('bhyt.ctdt.partials.than-chi-tiet', $nguon);
        $this->assertContains('bhyt.ctdt.partials.js-chi-tiet', $nguon);
    }

    /** @test */
    public function js_dung_chung_khong_nhung_ma_ho_so_vao_ma()
    {
        // JS gan MOT LAN, luc trang tai - luc do chua biet ho so nao se duoc mo. Nhung
        // @json($hoSo->ma_ho_so) vao day nghia la moi ho so mo sau deu dung ma cua ho so
        // dau tien.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertNotContains('$hoSo->ma_ho_so', $nguon,
            'ma ho so phai doc tu DOM (data-ma-ho-so), khong nhung vao JS');
        $this->assertNotContains('$hoSo->ma_gd', $nguon);
    }

    /** @test */
    public function js_dung_chung_phat_su_kien_chu_khong_tu_quyet_dieu_huong()
    {
        // JS khong duoc biet no dang o trang rieng hay trong modal. Mot cho goi
        // location.reload() thang trong day la buoc modal phai nap lai ca trang - mat bo loc.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertContains('ctdt:da-xep-hang', $nguon);
        $this->assertContains('ctdt:da-xoa', $nguon);
        $this->assertNotContains('location.reload', $nguon,
            'chu nha quyet dieu huong, khong phai JS dung chung');
    }

    /** @test */
    public function route_than_duoc_khai_bao()
    {
        // Route nay la thu duy nhat modal goi. Thieu no thi modal mo ra rong, va loi chi lo
        // ra trong console cua trinh duyet.
        $nguon = file_get_contents(base_path('routes/web.php'));

        $this->assertContains("bhyt.ctdt.detail.than", $nguon);
        $this->assertContains('ctdt/detail/{ma_ho_so}/than', $nguon);
    }

    /** @test */
    public function detailThan_tra_ve_than_chi_tiet_chu_khong_phai_ca_trang()
    {
        // Tra ca trang thi modal se chua mot ban AdminLTE thu hai - menu long trong menu.
        $nguon = file_get_contents(
            base_path('app/Http/Controllers/BHYT/BHYTCtdtController.php')
        );

        $this->assertContains("bhyt.ctdt.partials.than-chi-tiet", $nguon,
            'detailThan() phai tra partial, khong tra view bhyt.ctdt.detail');
    }

    /** @test */
    public function detailThan_render_ra_than_tran_khong_layout()
    {
        // Kiem tinh (grep @extends trong .blade.php) khong bat duoc mot @include layout an
        // trong partial, hay mot View::composer toan cuc gan them the. Chi render that moi
        // khang dinh duoc dau ra THAT cua detailThan() khong mang theo AdminLTE.
        $this->napMau();

        $ra = (string) $this->controller->detailThan('YT001')->render();

        $this->assertNotContains('<html', $ra);
        $this->assertNotContains('<body', $ra);
        $this->assertNotContains('main-sidebar', $ra,
            'main-sidebar la dau hieu rieng cua layout AdminLTE (aside menu ben trai)');

        $this->assertContains('ctdt-chi-tiet', $ra, 'phai la than that, khong phai dau ra rong');
        $this->assertContains('YT001', $ra);
    }
}
