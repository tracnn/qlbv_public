<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12DanhSach;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Canh man loc cua module TT12 dung lai cac partial dung chung, giong het cach
 * CtdtBoLocDungPartialsTest canh man loc cua chung tu dien tu.
 *
 * VI SAO RENDER THAT chu khong quet chuoi: quet chuoi se xanh ca khi Blade nem loi luc
 * render (thieu bien, sai ten partial). Man loc chi hong khi nguoi dung mo trang, ma
 * khong ai chay thu truoc moi lan sua.
 */
class Tt12BoLocDungPartialsTest extends TestCase
{
    private function danhSachMau()
    {
        $ds = array();

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $ds[$ma] = $lop::ten();
        }

        return $ds;
    }

    private function locTt12()
    {
        return view('bhyt.tt12.partials.search', array(
            'danhSachMau'  => $this->danhSachMau(),
            'danhSachCoSo' => array('01929' => 'Bệnh viện A', '37470' => 'Bệnh viện B'),
            'cacTrangThai' => Tt12DanhSach::cacTrangThai(),
        ))->render();
    }

    /** @test */
    public function man_loc_render_duoc_khong_nem()
    {
        $this->assertNotEmpty($this->locTt12());
    }

    /** @test */
    public function dung_lai_bon_partial_dung_chung()
    {
        // Bon partial nay mang theo HANH VI chu khong chi markup: date_range dung
        // daterangepicker, imported_by nap danh sach qua AJAX, load_data_button goi
        // fetchData kem kiem khoang ngay. Mat mot cai la mat hanh vi do, khong chi mat
        // mot o nhap.
        $html = $this->locTt12();

        foreach (array('date_range', 'ma_cskcb', 'imported_by', 'load_data_button') as $id) {
            $this->assertContains('id="' . $id . '"', $html, 'Thieu partial dung chung ' . $id);
        }
    }

    /** @test */
    public function van_giu_cac_bo_loc_rieng_cua_module()
    {
        $html = $this->locTt12();

        foreach (array('mau', 'trang_thai', 'tim') as $id) {
            $this->assertContains('id="' . $id . '"', $html, 'Thieu bo loc ' . $id);
        }
    }

    /** @test */
    public function o_chon_mau_liet_ke_du_sau_mau()
    {
        $html = $this->locTt12();

        foreach (array_keys(Tt12MauRegistry::tatCa()) as $ma) {
            $this->assertContains('value="' . $ma . '"', $html, 'Thieu mau ' . $ma);
        }
    }

    /** @test */
    public function o_chon_trang_thai_liet_ke_du_cac_trang_thai()
    {
        $html = $this->locTt12();

        foreach (array_keys(Tt12DanhSach::cacTrangThai()) as $ma) {
            $this->assertContains('value="' . $ma . '"', $html, 'Thieu trang thai ' . $ma);
        }
    }

    /** @test */
    public function khong_bat_nut_xuat_cua_date_range()
    {
        // TT12 co HAI nut xuat rieng (danh sach va bang loi) dat tren bang. Bat them nut
        // export_xlsx cua date_range se thanh nut thu ba khong noi vao dau.
        $this->assertNotContains('id="export_xlsx"', $this->locTt12());
    }

    /** @test */
    public function khong_con_nut_loc_tu_lam_tren_man_danh_sach()
    {
        // partials.load_data_button da lo viec tai du lieu, va no con them hieu ung cho
        // cung phep kiem khoang ngay. Giu lai nut cu nghia la hai nut cung goi mot viec,
        // moi nut mot duong - va duong cu khong kiem gi.
        $duongDan = resource_path('views/bhyt/tt12/index.blade.php');

        $this->assertNotContains('btn-loc', file_get_contents($duongDan));
    }

    /** @test */
    public function man_danh_sach_co_khoi_tao_select2()
    {
        // Cac o loc mang class 'select2' nhung class do chi la DANH DAU. Khong goi
        // .select2() thi chung hien nhu <select> tron - mat o tim kiem trong danh sach,
        // va khac han man CTDT / XML3176 von la thu man nay duoc yeu cau lam giong.
        $blade = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertNotFalse($blade);
        $this->assertContains(".select2').select2(", $blade,
            'index.blade.php phai khoi tao select2 cho cac o loc');
    }

    /** @test */
    public function moi_o_loc_deu_mang_class_select2()
    {
        $html = $this->locTt12();

        foreach (array('mau', 'trang_thai') as $id) {
            $this->assertRegExp(
                '/<select id="' . $id . '"[^>]*class="[^"]*select2/',
                $html,
                'O loc ' . $id . ' phai mang class select2'
            );
        }
    }

    /** @test */
    public function index_dinh_nghia_ham_fetchData_dung_ten()
    {
        // HOP DONG voi partials.load_data_button: nut do goi ham TOAN CUC
        // fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Doi
        // ten ham (vi du tt12FetchData) thi nut khong tim thay ham, va man hinh KHONG BAO
        // GIO tai du lieu - im lang, khong loi nao hien ra cho nguoi dung.
        $blade = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertContains('function fetchData(', $blade,
            'index.blade.php phai dinh nghia ham toan cuc fetchData()');
    }

    /** @test */
    public function khong_tao_DataTable_truoc_khi_biet_khoang_ngay()
    {
        // Tao bang o cap cao nhat thi DataTables ban AJAX NGAY luc parse - luc do
        // partials.date_range (chay trong document.ready) chua kip gan khoang ngay mac
        // dinh, nen luot dau di len may chu voi tu_ngay/den_ngay RONG va quet toan bo
        // bang tt12_ho_so khong gioi han ngay. Ngay sau do load_data_button goi
        // fetchData() lan nua - thanh hai truy van moi lan mo trang, cai dau vo ich va
        // nang nhat.
        //
        // Khuon CTDT tao bang LUOI ben trong fetchData() kem chot dung lai. Theo dung do.
        $blade = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        // Khang dinh CHINH XAC dieu phan biet dung/sai: bien duoc KHAI BAO rong o cap
        // cao nhat, va phep GAN nam trong fetchData(). Khang dinh theo vi tri
        // (.DataTable( xuat hien sau function fetchData() khong phan biet duoc gi - mot
        // loi tao o cap cao nhat dat ngay duoi ham van thoa.
        $this->assertContains('var tt12Bang = null;', $blade,
            'tt12Bang phai duoc khai bao rong, chua tao bang');
        $this->assertNotContains('var tt12Bang = $(', $blade,
            'Khong duoc tao DataTable o cap cao nhat - no ban AJAX ngay luc parse, '
            . 'truoc khi biet khoang ngay');
        $this->assertContains("tt12Bang = $('#tt12-list').DataTable(", $blade,
            'Phep gan phai nam trong fetchData()');
    }

    /** @test */
    public function index_day_du_bon_stack_script_cua_cac_partial()
    {
        // Moi partial tu dat script cua no vao mot stack rieng. Khong day ra thi partial
        // im lang khong hoat dong - o chon khoang thoi gian thanh mot o text tron, con
        // Enter trong o Tim khong lam gi.
        $html = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'))
            . file_get_contents(resource_path('views/bhyt/tt12/partials/search.blade.php'));

        foreach (array('after-scripts-date-range', 'after-scripts-imported-by',
                       'after-scripts-o-tim', 'after-scripts-load-data-button') as $stack) {
            $this->assertContains("@stack('" . $stack . "')", $html, 'Thieu stack ' . $stack);
        }
    }
}
