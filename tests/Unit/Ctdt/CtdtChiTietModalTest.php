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

    /** @test */
    public function man_danh_sach_co_khung_modal_va_nap_js_dung_chung()
    {
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('id="modal-ctdt"', $nguon, 'Thieu khung modal');
        $this->assertContains('bhyt.ctdt.partials.js-chi-tiet', $nguon,
            'Khong nap JS dung chung thi nut trong modal se im lang');
        $this->assertContains('modal-xxl', $nguon,
            'Dung lai lop modal-xxl da co trong public/css/customize.css');
    }

    /** @test */
    public function man_danh_sach_nghe_ca_hai_su_kien()
    {
        // Thieu mot trong hai thi modal van mo duoc, van gui duoc, nhung bang khong bao gio
        // cap nhat - nguoi dung se bam gui lan hai.
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('ctdt:da-xep-hang', $nguon);
        $this->assertContains('ctdt:da-xoa', $nguon);
    }

    /** @test */
    public function nut_chi_tiet_van_la_the_a_co_href_that()
    {
        // Ctrl+click va chuot giua phai mo duoc tab moi. <a href="#"> hoac <button> se giet
        // hanh vi do - va do la thoi quen cua dung nhung nguoi dung man nay nhieu nhat.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotContains('href="#"', $nguon,
            'Nut mo chi tiet phai tro URL that de ctrl+click con mo duoc tab moi');
    }

    /** @test */
    public function hai_ham_render_khong_noi_chuoi_vao_thuoc_tinh_data_ma_ho_so()
    {
        // $('<div>').text(x).html() CHI thoat '&', '<', '>' - KHONG thoat dau nhay kep. Noi
        // gia tri da "an" kieu do vao mot THUOC TINH van cho phep mot MA_YTE dang
        // 'A" onmouseover=... x="' thoat ra khoi thuoc tinh va chay ngay khi mo man danh
        // sach, voi phien cua chinh nguoi co quyen bam "Ky va gui".
        //
        // CtdtMaHoSo::cua() lay MA_YTE nguyen van tu XML va chi kiem do dai, khong kiem bo
        // ky tu - nen gia tri do vao duoc that.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotRegExp('/data-ma-ho-so="\'\s*\+/', $nguon,
            'khong duoc noi chuoi vao thuoc tinh data-ma-ho-so; dung .attr() roi lay outerHTML');

        $this->assertSame(2, substr_count($nguon, ".attr('data-ma-ho-so', data)"),
            'ca hai cot (ma_ho_so va action) deu phai dung .attr() de dat data-ma-ho-so');
    }

    /** @test */
    public function mo_modal_co_ma_the_he_o_ca_done_lan_fail()
    {
        // Bam ho so A (cham) -> dong modal -> bam ho so B (nhanh) -> B hien -> roi A ve muon
        // va DE LEN than dang mang ten B. Nut "Ky va gui" trong than do se POST HO SO A len
        // cong BHXH. Khoa phia may chu (CtdtXepHangKyGui) khong do duoc vi A la ho so khac,
        // hoan toan chua bi khoa.
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('var luot = ++luotMoModal;', $nguon,
            'moi lan mo modal phai lay mot so the he');

        $this->assertSame(2, substr_count($nguon, 'if (luot !== luotMoModal) {'),
            'phep kiem the he phai co o CA HAI nhanh .done va .fail');
    }

    /** @test */
    public function duong_dan_than_dung_bang_duong_dan_chi_tiet_cong_than()
    {
        // index.blade.php dung $(this).attr('href') + '/than' de dung URL. Phep ghep do CHI
        // dung khi route than la route chi tiet cong '/than'. Doi mot ben ma quen ben kia thi
        // modal 404 va chi lo ra khi co nguoi bam.
        $chiTiet = route('bhyt.ctdt.detail', ['ma_ho_so' => 'YT001']);
        $than = route('bhyt.ctdt.detail.than', ['ma_ho_so' => 'YT001']);

        $this->assertSame($chiTiet . '/than', $than);
    }

    /** @test */
    public function man_danh_sach_khong_nhung_than_chi_tiet_thang_vao_trang()
    {
        // Than dung dinh danh don (#ctdt-tabs, #noi-dung-tab, #btn-ky-va-gui). Co HAI than
        // cung luc thi $('#noi-dung-tab') va $('#ctdt-tabs li') chi trung phan tu DAU TIEN:
        // bam tab o than thu hai lai nap noi dung vao than thu nhat - im lang, khong loi
        // console. Trong khi $(document).on('click', '#btn-ky-va-gui') lai khop CA HAI.
        // Than chi duoc nap qua AJAX vao modal.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotContains('partials.than-chi-tiet', $nguon,
            'than chi duoc nap bang AJAX vao modal, khong @include thang vao man danh sach');
    }

    /** @test */
    public function nap_lai_bang_giu_nguyen_bo_loc_va_trang_dang_xem()
    {
        // Day la LY DO TON TAI cua ca tinh nang. Mat 'null, false' thi moi lan gui xong man
        // nhay ve trang 1 va nguoi xu nhieu ho so lien tiep phai loc lai tu dau - dung thu
        // ma nhanh nay hua chua.
        $nguon = $this->nguon('index.blade.php');

        // Khang dinh cau LENH chu khong phai chuoi: 'ajax.reload(null, false)' con xuat hien
        // trong chinh chu thich ngay tren no, nen thieu dau ';' thi test xanh gia.
        $this->assertContains('ajax.reload(null, false);', $nguon,
            'phai giu bo loc va trang dang xem khi nap lai bang');
    }

    /** @test */
    public function xoa_ho_so_co_bao_thanh_cong_truoc_khi_phat_su_kien()
    {
        // Trong modal, xoa xong thi modal dong va bang nap lai - khong mot thong diep nao
        // noi "da xoa". Voi thao tac khong hoan tac thi phai co xac nhan da xong.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $viTriBao = strpos($nguon, "title: 'Đã xóa'");
        $viTriPhat = strpos($nguon, "trigger('ctdt:da-xoa'");

        $this->assertNotFalse($viTriBao, 'thieu thong diep bao xoa thanh cong');
        $this->assertNotFalse($viTriPhat);
        $this->assertTrue($viTriBao < $viTriPhat, 'phai bao thanh cong TRUOC khi phat su kien');
    }
}
