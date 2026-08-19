<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Canh man loc cua module chung tu dien tu dung lai cac partial dung chung, va canh viec
 * lam nut Export co dieu kien KHONG lam mat nut o cac man khac.
 *
 * VI SAO RENDER THAT chu khong quet chuoi: quet chuoi se xanh ca khi Blade nem loi luc
 * render (thieu bien, sai ten partial). Man loc chi hong khi nguoi dung mo trang, ma
 * khong ai chay thu truoc moi lan sua.
 */
class CtdtBoLocDungPartialsTest extends TestCase
{
    private function locCtdt()
    {
        return view('bhyt.ctdt.partials.search', [
            'danhSachCoSo'      => ['01929' => 'Bệnh viện A', '01013' => 'Bệnh viện B'],
            'danhSachLoai'      => ['CT03' => 'Giấy ra viện', 'CT04' => 'Tóm tắt hồ sơ bệnh án'],
            'danhSachTrangThai' => CtdtTrangThaiGui::NHAN,
        ])->render();
    }

    /** @test */
    public function man_loc_render_duoc_khong_nem()
    {
        $this->assertNotEmpty($this->locCtdt());
    }

    /** @test */
    public function dung_lai_bon_partial_dung_chung()
    {
        // Bon partial nay mang theo hanh vi chu khong chi markup: date_range dung
        // daterangepicker, imported_by nap danh sach qua AJAX, load_data_button goi
        // fetchData. Mat mot cai la mat hanh vi do, khong chi mat mot o nhap.
        $html = $this->locCtdt();

        foreach (['date_range', 'ma_cskcb', 'imported_by', 'load_data_button'] as $id) {
            $this->assertContains('id="' . $id . '"', $html, 'Thieu partial dung chung ' . $id);
        }
    }

    /** @test */
    public function van_giu_cac_bo_loc_rieng_cua_module()
    {
        $html = $this->locCtdt();

        foreach (['dich_vu', 'loai_ho_so', 'trang_thai_gui', 'chi_con_loi', 'tim'] as $id) {
            $this->assertContains('id="' . $id . '"', $html, 'Thieu bo loc ' . $id);
        }
    }

    /** @test */
    public function khong_con_nut_tai_du_lieu_tu_lam()
    {
        // partials.load_data_button da lo viec do, va no con them hieu ung cho. Giu lai
        // nut cu nghia la hai nut cung goi mot viec, moi nut mot duong.
        $this->assertNotContains('btn_tai_du_lieu', $this->locCtdt());
    }

    /** @test */
    public function khong_hien_nut_export_vi_module_chua_co_duong_xuat()
    {
        // Xuat Excel thuoc Giai doan 5. De nut o day se la mot nut chet.
        $this->assertNotContains('id="export_xlsx"', $this->locCtdt());
    }

    /** @test */
    public function date_range_mac_dinh_van_hien_nut_export()
    {
        $this->assertContains('id="export_xlsx"', view('partials.date_range')->render());
    }

    /** @test */
    public function date_range_an_nut_export_khi_duoc_yeu_cau()
    {
        $html = view('partials.date_range', ['showExport' => false])->render();

        $this->assertNotContains('id="export_xlsx"', $html);
        $this->assertContains('id="date_range"', $html, 'Van phai con o chon khoang thoi gian');
    }

    /** @test */
    public function cac_man_dang_dung_date_range_khong_bi_mat_nut_export()
    {
        // Hai mươi mấy màn khác include partial nay ma khong truyen showExport. Mac dinh
        // phai la HIEN, khong thi mot thay doi cua module nay lam mat nut xuat cua ca he
        // thong - va khong test nao cua ho bat duoc.
        $xml3176 = file_get_contents(base_path('resources/views/bhyt/xml3176/partials/search.blade.php'));

        $this->assertNotContains('showExport', $xml3176,
            'Man XML3176 khong truyen showExport, nen no phu thuoc gia tri mac dinh');
        $this->assertContains('id="export_xlsx"', view('partials.date_range')->render());
    }
}
