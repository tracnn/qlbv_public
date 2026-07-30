<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\CuaSoQuet;
use Tests\TestCase;

class CuaSoQuetTest extends TestCase
{
    /** @test */
    public function ket_thuc_la_moc_cong_cua_so()
    {
        $this->assertSame(51000, CuaSoQuet::ketThuc(1000, 50000));
    }

    /** @test */
    public function cua_so_bang_khong_nghia_la_khong_chan()
    {
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, 0));
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, -5));
    }

    /** @test */
    public function lay_du_limit_thi_tien_toi_id_lon_nhat_trong_lo()
    {
        // Cua so CHUA duyet het: con dong chua lay, chi duoc tien toi cho da lay den.
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 500, 500, 1400, 51000));
    }

    /** @test */
    public function lay_it_hon_limit_thi_nhay_toi_cuoi_cua_so()
    {
        // Cua so DA duyet het: nhay qua ca khoang trong con lai.
        $this->assertSame(51000, CuaSoQuet::mocMoi(1000, 120, 500, 1400, 51000));
    }

    /**
     * Ca quan trong nhat: cua so RONG.
     *
     * Khong nhay thi luot sau lai hoi dung cua so do, lai 0 dong, va bo quet DUNG IM
     * VINH VIEN - im lang, khong loi nao bao ra.
     */
    /** @test */
    public function khong_lay_duoc_dong_nao_thi_van_nhay_toi_cuoi_cua_so()
    {
        $this->assertSame(51000, CuaSoQuet::mocMoi(1000, 0, 500, 0, 51000));
    }

    /** @test */
    public function khong_chan_cua_so_thi_luon_lay_id_lon_nhat_trong_lo()
    {
        // cuoiCuaSo = 0 nghia la khong chan: giu nguyen hanh vi cu.
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 120, 500, 1400, 0));
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 500, 500, 1400, 0));
    }

    /** @test */
    public function khong_bao_gio_lui_moc()
    {
        // Lo tra ve id nho hon moc (khong nen xay ra, nhung neu xay ra thi phai giu moc).
        $this->assertSame(1000, CuaSoQuet::mocMoi(1000, 500, 500, 900, 0));
        $this->assertSame(1000, CuaSoQuet::mocMoi(1000, 0, 500, 0, 0));
    }
}
