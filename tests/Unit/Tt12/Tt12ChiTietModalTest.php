<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Http\Controllers\BHYT\BHYTTt12Controller;

/**
 * Chot canh cho man chi tiet TT12 dang modal.
 *
 * VAN DE GOC: than chi tiet duoc nap bang AJAX vao modal tren man danh sach. Moi khoi
 * @push('after-scripts') trong mot fragment nap kieu do bi bo di KHONG MOT LOI BAO - nut
 * "Ky va gui" van hien, van bam duoc, va khong co gi xay ra. Do la hang loi te nhat: im
 * lang, va nam dung tren nut nguy hiem nhat cua module.
 *
 * Cac test duoi day canh CAU TRUC chu khong dua vao ky luat cua nguoi sua sau. Chung soi
 * ma nguon blade truc tiep vi day la nhung bat bien khong the hien ra qua dau ra render.
 *
 * Doi xung voi CtdtChiTietModalTest - hai module cung mot khuon.
 */
class Tt12ChiTietModalTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
    }

    /** @return string noi dung tho cua mot view blade */
    private function nguon($duongDanTuongDoi)
    {
        $duongDan = resource_path('views/bhyt/tt12/' . $duongDanTuongDoi);

        $this->assertFileExists($duongDan);

        return file_get_contents($duongDan);
    }

    /** Mot ho so du de detailThan() render duoc */
    private function napMau()
    {
        $hoSo = Tt12HoSo::create(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
        ));

        Tt12Dong::create(array(
            'ho_so_id' => $hoSo->id, 'stt' => 1,
            'du_lieu' => array('STT' => '1', 'MA_KHOA' => 'K01'),
        ));

        return $hoSo;
    }

    // -- Than: phai la fragment tran ------------------------------------------------

    /** @test */
    public function than_chi_tiet_khong_chua_mot_dong_script_nao()
    {
        // Fragment khong co script thi khong co gi de roi. Day la chot canh chinh: no bien
        // "dung quen @push" tu mot loi dan thanh mot bat bien co the kiem.
        $this->assertNotContains('<script', $this->nguon('partials/than-chi-tiet.blade.php'),
            'than-chi-tiet la fragment nap bang AJAX - script trong do se khong bao gio chay');
        // '@push(' chu khong phai '@push': chu thich dau tep CO Y nhac toi chi thi do (viet
        // '@@push' de Blade khong dich), va chuoi ngan hon se khop chinh doan chu thich ay.
        $this->assertNotContains('@push(', $this->nguon('partials/than-chi-tiet.blade.php'),
            '@push trong fragment nap bang AJAX roi khong dau vet');
    }

    /** @test */
    public function than_chi_tiet_la_fragment_chu_khong_phai_trang()
    {
        $nguon = $this->nguon('partials/than-chi-tiet.blade.php');

        $this->assertNotContains('@extends', $nguon);
        $this->assertNotContains('@section', $nguon);
    }

    /** @test */
    public function trang_chi_tiet_dung_lai_hai_partial_chu_khong_chep_lai()
    {
        // Trang rieng va modal PHAI dung chung mot ban than. Hai ban se lech nhau, va khong
        // co dau hieu gi cho toi luc ai do doi chieu tung dong.
        $nguon = $this->nguon('detail.blade.php');

        $this->assertContains('bhyt.tt12.partials.than-chi-tiet', $nguon);
        $this->assertContains('bhyt.tt12.partials.js-chi-tiet', $nguon);
    }

    // -- JS dung chung ---------------------------------------------------------------

    /** @test */
    public function js_dung_chung_khong_nhung_ma_ho_so_vao_ma()
    {
        // JS gan MOT LAN, luc trang tai - luc do chua biet ho so nao se duoc mo. Nhung
        // $hoSo->ma_ho_so vao day nghia la moi ho so mo sau deu dung ma cua ho so dau tien.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertNotContains('$hoSo->ma_ho_so', $nguon,
            'ma ho so phai doc tu DOM (data-ma-ho-so), khong nhung vao JS');
    }

    /** @test */
    public function js_dung_chung_phat_su_kien_chu_khong_tu_quyet_dieu_huong()
    {
        // JS khong duoc biet no dang o trang rieng hay trong modal. Mot cho goi
        // location.reload() thang trong day la buoc modal phai nap lai ca trang - mat bo loc.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertContains('tt12:da-xep-hang', $nguon);
        $this->assertContains('tt12:da-cuu-ho', $nguon);
        $this->assertNotContains('location.reload', $nguon,
            'chu nha quyet dieu huong, khong phai JS dung chung');
    }

    /** @test */
    public function ca_ba_nut_deu_phat_su_kien()
    {
        // Ky va gui, kiem lai, dong bo lai - ba nut, hai su kien. Thieu mot cho phat thi nut
        // do bam xong khong co gi xay ra tiep: bang khong cap nhat, modal khong dong.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertContains('#btn-ky-va-gui', $nguon);
        $this->assertContains('#btn-kiem-lai', $nguon);
        $this->assertContains('#btn-dong-bo-lai', $nguon);

        $this->assertSame(1, substr_count($nguon, "trigger('tt12:da-xep-hang'"));

        // Hai nut cuu ho di chung mot khuon goi bamCuuHo(), nen chi co MOT cho phat su kien.
        // Khang dinh o day la ca hai deu di qua khuon do - nut nao tu goi $.post rieng se
        // lech khoi khuon va som muon cung quen phat su kien.
        $this->assertSame(1, substr_count($nguon, "trigger('tt12:da-cuu-ho'"));
        $this->assertSame(2, substr_count($nguon, 'bamCuuHo($(this)'),
            'ca kiem-lai lan dong-bo-lai deu phai di qua bamCuuHo()');
    }

    /** @test */
    public function js_dung_chung_uy_nhiem_su_kien_tren_document()
    {
        // Gan truc tiep ($('#btn-ky-va-gui').on) chi thay phan tu CO SAN luc trang tai. Than
        // trong modal vao DOM sau do, nen nut se khong co handler nao - im lang.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertNotRegExp("/\\\$\('#btn-ky-va-gui'\)\.on\(/", $nguon,
            'phai uy nhiem qua $(document).on, khong gan truc tiep vao nut');
    }

    // -- Route + controller -----------------------------------------------------------

    /** @test */
    public function route_than_duoc_khai_bao()
    {
        // Route nay la thu duy nhat modal goi. Thieu no thi modal mo ra rong, va loi chi lo
        // ra trong console cua trinh duyet.
        $nguon = file_get_contents(base_path('routes/web.php'));

        $this->assertContains('bhyt.tt12.detail.than', $nguon);
        $this->assertContains('tt12/detail/{ma_ho_so}/than', $nguon);
    }

    /** @test */
    public function duong_dan_than_dung_bang_duong_dan_chi_tiet_cong_than()
    {
        // index.blade.php dung $(this).attr('href') + '/than' de dung URL. Phep ghep do CHI
        // dung khi route than la route chi tiet cong '/than'. Doi mot ben ma quen ben kia thi
        // modal 404 va chi lo ra khi co nguoi bam.
        $chiTiet = route('bhyt.tt12.detail', array('ma_ho_so' => 'HS001'));
        $than = route('bhyt.tt12.detail.than', array('ma_ho_so' => 'HS001'));

        $this->assertSame($chiTiet . '/than', $than);
    }

    /** @test */
    public function detailThan_tra_ve_than_chi_tiet_chu_khong_phai_ca_trang()
    {
        // Tra ca trang thi modal se chua mot ban AdminLTE thu hai - menu long trong menu.
        $nguon = file_get_contents(
            base_path('app/Http/Controllers/BHYT/BHYTTt12Controller.php')
        );

        $this->assertContains('bhyt.tt12.partials.than-chi-tiet', $nguon,
            'detailThan() phai tra partial, khong tra view bhyt.tt12.detail');
    }

    /** @test */
    public function detailThan_render_ra_than_tran_khong_layout()
    {
        // Kiem tinh (grep @extends trong .blade.php) khong bat duoc mot @include layout an
        // trong partial, hay mot View::composer toan cuc gan them the. Chi render that moi
        // khang dinh duoc dau ra THAT cua detailThan() khong mang theo AdminLTE.
        $hoSo = $this->napMau();

        $ra = (string) (new BHYTTt12Controller())->detailThan($hoSo->ma_ho_so)->render();

        $this->assertNotContains('<html', $ra);
        $this->assertNotContains('<body', $ra);
        $this->assertNotContains('main-sidebar', $ra,
            'main-sidebar la dau hieu rieng cua layout AdminLTE (aside menu ben trai)');

        $this->assertContains('tt12-chi-tiet', $ra, 'phai la than that, khong phai dau ra rong');
        $this->assertContains($hoSo->ma_ho_so, $ra);
    }

    // -- Man danh sach -----------------------------------------------------------------

    /** @test */
    public function man_danh_sach_co_khung_modal_va_nap_js_dung_chung()
    {
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('id="modal-tt12"', $nguon, 'Thieu khung modal');
        $this->assertContains('bhyt.tt12.partials.js-chi-tiet', $nguon,
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

        $this->assertContains('tt12:da-xep-hang', $nguon);
        $this->assertContains('tt12:da-cuu-ho', $nguon);
    }

    /** @test */
    public function nut_chi_tiet_van_la_the_a_co_href_that()
    {
        // Ctrl+click va chuot giua phai mo duoc tab moi. <a href="#"> hoac <button> se giet
        // hanh vi do - va do la thoi quen cua dung nhung nguoi dung man nay nhieu nhat.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotContains('href="#"', $nguon,
            'Nut mo chi tiet phai tro URL that de ctrl+click con mo duoc tab moi');
        // Ba lan: hai cho render (cot ma ho so + nut "Chi tiet") va mot bo chon trong
        // handler mo modal. Thieu mot cho render thi duong do van mo trang rieng - dung
        // nhung khong nhat quan, va khong co dau hieu gi bao.
        $this->assertSame(3, substr_count($nguon, 'tt12-mo-chi-tiet'),
            'ca cot ma ho so lan nut Chi tiet deu phai mo modal');
        $this->assertSame(2, substr_count($nguon, "addClass('tt12-mo-chi-tiet')")
            + substr_count($nguon, "btn btn-xs btn-default tt12-mo-chi-tiet"),
            'ca hai cho render deu phai gan class mo modal');
    }

    /** @test */
    public function hai_ham_render_khong_noi_chuoi_vao_thuoc_tinh_data_ma_ho_so()
    {
        // $('<div>').text(x).html() CHI thoat '&', '<', '>' - KHONG thoat dau nhay kep. Noi
        // gia tri da "an" kieu do vao mot THUOC TINH van cho phep mot ma ho so dang
        // 'A" onmouseover=... x="' thoat ra khoi thuoc tinh va chay ngay khi mo man danh
        // sach, voi phien cua chinh nguoi co quyen bam "Ky va gui".
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
        // cong BHXH. Khoa phia may chu khong do duoc vi A la ho so khac, hoan toan chua bi
        // khoa.
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('var luot = ++luotMoModal;', $nguon,
            'moi lan mo modal phai lay mot so the he');

        $this->assertSame(2, substr_count($nguon, 'if (luot !== luotMoModal) {'),
            'phep kiem the he phai co o CA HAI nhanh .done va .fail');
    }

    /** @test */
    public function man_danh_sach_khong_nhung_than_chi_tiet_thang_vao_trang()
    {
        // Than dung dinh danh don (#tt12-tabs, #noi-dung-tab, #btn-ky-va-gui). Co HAI than
        // cung luc thi $('#noi-dung-tab') va $('#tt12-tabs li') chi trung phan tu DAU TIEN:
        // bam tab o than thu hai lai nap noi dung vao than thu nhat - im lang, khong loi
        // console. Trong khi $(document).on('click', '#btn-ky-va-gui') lai khop CA HAI.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotContains('partials.than-chi-tiet', $nguon,
            'than chi duoc nap bang AJAX vao modal, khong @include thang vao man danh sach');
    }

    /** @test */
    public function nap_lai_bang_giu_nguyen_bo_loc_va_trang_dang_xem()
    {
        // Day la LY DO TON TAI cua ca tinh nang. Mat 'null, false' thi moi lan gui xong man
        // nhay ve trang 1 va nguoi xu nhieu ho so lien tiep phai loc lai tu dau.
        //
        // Khang dinh cau LENH chu khong phai chuoi: 'ajax.reload(null, false)' con xuat hien
        // trong chinh chu thich ngay tren no, nen thieu dau ';' thi test xanh gia.
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('ajax.reload(null, false);', $nguon,
            'phai giu bo loc va trang dang xem khi nap lai bang');
    }
}
