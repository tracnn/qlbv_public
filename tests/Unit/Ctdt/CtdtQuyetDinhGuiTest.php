<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\LocComment;
use App\Services\Ctdt\CtdtQuyetDinhGui;

/**
 * Ham THUAN: bon tham so vao, mot chuoi ra. Khong doc config, khong doc CSDL - nen kiem
 * duoc het cac to hop ma khong can dung mot bang nao.
 */
class CtdtQuyetDinhGuiTest extends TestCase
{
    use LocComment;

    /** @test */
    public function du_dieu_kien_thi_GUI()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::GUI,
            CtdtQuyetDinhGui::nen(true, true, 0, true)
        );
    }

    /** @test */
    public function co_tat_thi_KHONG_GUI_du_moi_thu_khac_deu_dat()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::KHONG_GUI,
            CtdtQuyetDinhGui::nen(false, true, 0, true)
        );
    }

    /** @test */
    public function co_tat_thang_moi_ly_do_khac()
    {
        // THU TU QUAN TRONG: khi chuc nang gui dang tat thi khong co lan gui nao dien ra,
        // nen ghi submit_error "con loi" hay "chua ky" la BIA - nguoi doc se tuong da thu
        // gui va that bai. Co phai duoc hoi TRUOC MOI thu khac.
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, false, 0, false));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, true, 9, false));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, false, 9, true));
    }

    /** @test */
    public function chua_kiem_thi_CHUA_KIEM_du_so_loi_bang_khong()
    {
        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach - no co nghia la chua
        // ai nhin. Gui len cong mot ho so chua qua bo kiem la dung thu ma ca Giai doan 3
        // ton tai de chan.
        $this->assertSame(
            CtdtQuyetDinhGui::CHUA_KIEM,
            CtdtQuyetDinhGui::nen(true, false, 0, true)
        );
    }

    /** @test */
    public function chua_kiem_thang_con_loi_va_chua_ky()
    {
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, false, 5, true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, false, 5, false));
    }

    /** @test */
    public function con_loi_thi_CON_LOI()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::CON_LOI,
            CtdtQuyetDinhGui::nen(true, true, 1, true)
        );
    }

    /** @test */
    public function con_loi_thang_chua_ky()
    {
        // Da kiem, con loi, chua ky: viec can lam truoc la SUA HO SO, khong phai di ky.
        $this->assertSame(
            CtdtQuyetDinhGui::CON_LOI,
            CtdtQuyetDinhGui::nen(true, true, 3, false)
        );
    }

    /** @test */
    public function da_kiem_sach_nhung_chua_ky_thi_CHUA_KY()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::CHUA_KY,
            CtdtQuyetDinhGui::nen(true, true, 0, false)
        );
    }

    /** @test */
    public function ep_kieu_long_cho_moi_tham_so()
    {
        // is_signed doc tu MySQL tinyint(1) ve dang 0/1; cau hinh co the la chuoi;
        // checked_at la chuoi ngay gio hoac null; so_loi co the ve dang chuoi.
        // So sanh nghiem ngat o day se phan nhanh sai mot cach im lang.
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen(1, '2026-08-20 08:00:00', '0', 1));
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen('1', 1, 0, '1'));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(0, 1, 0, 1));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(null, 1, 0, 1));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, null, 0, true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, '', 0, true));
        $this->assertSame(CtdtQuyetDinhGui::CON_LOI, CtdtQuyetDinhGui::nen(true, 1, '2', true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KY, CtdtQuyetDinhGui::nen(true, 1, 0, null));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KY, CtdtQuyetDinhGui::nen(true, 1, 0, '0'));
    }

    /** @test */
    public function so_loi_am_khong_duoc_coi_la_con_loi()
    {
        // Khong luong duoc so am tu luong that, nhung ep (int) roi so sanh > 0 la cach
        // duy nhat khong bao gio chan nham mot ho so sach vi mot gia tri rac.
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen(true, 1, -1, true));
    }

    /** @test */
    public function KHONG_dung_lai_lop_QuyetDinhGui_cua_xml3176()
    {
        // BO COMMENT truoc khi quet: docblock cua lop CO nhac ten App\Services\Xml3176\
        // QuyetDinhGui - va no phai nhac, vi nguoi doc sau nay can biet lop nao da bi co y
        // khong dung lai. Quet ca comment la cam mot cau giai thich dang lam viec huu ich.
        // Bat bien that su la MA khong tham chieu toi lop do.
        $ma = $this->maKhongComment(base_path('app/Services/Ctdt/CtdtQuyetDinhGui.php'));

        $this->assertNotContains('Xml3176', $ma,
            'CtdtQuyetDinhGui phai doc lap voi lop cua xml3176 trong MA, khong chi trong y dinh');
    }

    /** @test */
    public function KHONG_lam_vo_chu_ky_hai_tham_so_cua_lop_dung_chung()
    {
        // Day moi la thu that su vo ra se lam hong Xml3176Service va Qd130XmlService: chung
        // goi nen() voi dung HAI tham so. Neu ai do sau nay them tham so bat buoc vao lop
        // dung chung, hai duong san pham do do ngay - nhung khong test nao cua Giai doan 4
        // bat duoc, tru test nay.
        $ma = $this->maKhongComment(base_path('app/Services/Xml3176/QuyetDinhGui.php'));

        $this->assertContains('function nen($guiBat, $daKy)', $ma,
            'Lop dung chung phai giu nguyen chu ky hai tham so');
    }
}
