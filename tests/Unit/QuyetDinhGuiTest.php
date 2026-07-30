<?php

namespace Tests\Unit;

use App\Services\Xml3176\QuyetDinhGui;
use Tests\TestCase;

class QuyetDinhGuiTest extends TestCase
{
    /** @test */
    public function bat_gui_va_da_ky_thi_gui()
    {
        $this->assertSame('gui', QuyetDinhGui::nen(true, true));
    }

    /** @test */
    public function bat_gui_nhung_chua_ky_thi_bao_chua_ky()
    {
        $this->assertSame('chua_ky', QuyetDinhGui::nen(true, false));
    }

    /**
     * Diem de sai nhat: tat gui thi KHONG duoc ra 'chua_ky'. Khong co lan gui nao dien ra nen
     * ghi submit_error la bia - nguoi doc se tuong da thu gui va that bai.
     */
    /** @test */
    public function tat_gui_thi_khong_lam_gi_ca_du_chua_ky()
    {
        $this->assertSame('khong_gui', QuyetDinhGui::nen(false, false));
    }

    /** @test */
    public function tat_gui_thi_khong_lam_gi_ca_du_da_ky()
    {
        $this->assertSame('khong_gui', QuyetDinhGui::nen(false, true));
    }

    /**
     * is_signed doc tu MySQL tinyint(1) ve dang 0/1 chu khong phai true/false, va cau hinh
     * co the la chuoi. So sanh nghiem ngat se phan nhanh sai mot cach im lang.
     */
    /** @test */
    public function phan_nhanh_dung_voi_gia_tri_khong_phai_bool()
    {
        $this->assertSame('gui', QuyetDinhGui::nen(1, 1));
        $this->assertSame('chua_ky', QuyetDinhGui::nen(1, 0));
        $this->assertSame('khong_gui', QuyetDinhGui::nen(0, 1));

        $this->assertSame('gui', QuyetDinhGui::nen('1', '1'));
        $this->assertSame('chua_ky', QuyetDinhGui::nen('1', ''));

        $this->assertSame('khong_gui', QuyetDinhGui::nen(null, true));
        $this->assertSame('chua_ky', QuyetDinhGui::nen(true, null));
    }
}
