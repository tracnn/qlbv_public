<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12QuyetDinhGui;

/**
 * MOT noi tra loi cau hoi "co duoc ky khong" va "co duoc gui khong".
 *
 * VI SAO TACH RA MOT LOP RIENG: cau hoi nay duoc hoi o BA cho - job ky, job gui, va nut
 * bam tren man hinh. Viet lai luat o ba cho la ba ban se lech nhau, va ban lech se cho
 * gui mot ho so con loi.
 */
class Tt12QuyetDinhGuiTest extends TestCase
{
    /** @test */
    public function ho_so_da_kiem_va_sach_thi_duoc_ky()
    {
        $this->assertSame(Tt12QuyetDinhGui::KY, Tt12QuyetDinhGui::nenKy('2026-08-25 10:00:00', 0));
    }

    /** @test */
    public function ho_so_chua_kiem_thi_khong_ky()
    {
        // Ky la thao tac ton thoi gian nhat trong chuoi. Ky mot ho so chua kiem la co the
        // ky xong roi phat hien no khong duoc gui.
        $this->assertSame(Tt12QuyetDinhGui::CHUA_KIEM, Tt12QuyetDinhGui::nenKy(null, 0));
        $this->assertSame(Tt12QuyetDinhGui::CHUA_KIEM, Tt12QuyetDinhGui::nenKy('', 0));
    }

    /** @test */
    public function ho_so_con_loi_thi_khong_ky()
    {
        $this->assertSame(Tt12QuyetDinhGui::CON_LOI, Tt12QuyetDinhGui::nenKy('2026-08-25 10:00:00', 1));
        $this->assertSame(Tt12QuyetDinhGui::CON_LOI, Tt12QuyetDinhGui::nenKy('2026-08-25 10:00:00', 99));
    }

    /** @test */
    public function ho_so_da_ky_va_chua_gui_thi_duoc_gui()
    {
        $this->assertSame(Tt12QuyetDinhGui::GUI, Tt12QuyetDinhGui::nenGui(true, null));
        $this->assertSame(Tt12QuyetDinhGui::GUI, Tt12QuyetDinhGui::nenGui(true, ''));
    }

    /** @test */
    public function ho_so_gui_hong_lan_truoc_thi_duoc_gui_lai()
    {
        $this->assertSame(Tt12QuyetDinhGui::GUI, Tt12QuyetDinhGui::nenGui(true, '500'));
        $this->assertSame(Tt12QuyetDinhGui::GUI, Tt12QuyetDinhGui::nenGui(true, '401'));
    }

    /** @test */
    public function ho_so_chua_ky_thi_khong_gui()
    {
        $this->assertSame(Tt12QuyetDinhGui::CHUA_KY, Tt12QuyetDinhGui::nenGui(false, null));
    }

    /** @test */
    public function ho_so_da_duoc_tiep_nhan_thi_khong_gui_lai()
    {
        // Mot cu bam nham se sinh hai maGiaoDich cho cung mot danh muc, va co quan BHXH
        // nhan hai lan cung mot bo du lieu.
        $this->assertSame(Tt12QuyetDinhGui::DA_TIEP_NHAN, Tt12QuyetDinhGui::nenGui(true, '200'));
    }

    /** @test */
    public function ma_ket_qua_dang_so_nguyen_van_duoc_nhan_ra()
    {
        // PHP ep khoa mang dang chuoi so thanh int o nhieu cho. So sanh long, khong ===.
        $this->assertSame(Tt12QuyetDinhGui::DA_TIEP_NHAN, Tt12QuyetDinhGui::nenGui(true, 200));
    }
}
