<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\CuaSoQuet;
use Tests\TestCase;

class CuaSoQuetTest extends TestCase
{
    /** @test */
    public function ket_thuc_la_moc_cong_cua_so_khi_con_ton_dong()
    {
        // maxIdThat rat lon: con nhieu du lieu phia sau, cua so khong bi chan boi no.
        $this->assertSame(51000, CuaSoQuet::ketThuc(1000, 50000, 999999999));
    }

    /** @test */
    public function cua_so_bang_khong_nghia_la_khong_chan()
    {
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, 0, 999999999));
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, -5, 999999999));
    }

    /**
     * CRITICAL: da bat kip duoi bang (maxIdThat == moc, khong con dong nao phia sau). Neu
     * khong chan boi maxIdThat, cuoiCuaSo se la mot id CHUA TON TAI - day chinh la loi da
     * tim thay o ban review truoc: moc chay tron khoi du lieu that.
     */
    /** @test */
    public function da_bat_kip_thi_ket_thuc_khong_vuot_qua_max_id_that()
    {
        $this->assertSame(4407, CuaSoQuet::ketThuc(4407, 50000, 4407));
    }

    /** @test */
    public function da_bat_kip_thi_moc_moi_giu_nguyen_khong_nhay()
    {
        $cuoiCuaSo = CuaSoQuet::ketThuc(4407, 50000, 4407);
        // Lay 0 dong (khong con gi de lay) - truoc day se nhay toi 4407 + 50000 = 54407.
        $mocMoi = CuaSoQuet::mocMoi(4407, 0, 500, 0, $cuoiCuaSo);

        $this->assertSame(4407, $mocMoi);
    }

    /** @test */
    public function con_ton_dong_thi_ket_thuc_la_moc_cong_cua_so_nhu_cu()
    {
        // maxIdThat (200000) lon hon moc + cuaSo (51000): cua so khong cham maxIdThat.
        $this->assertSame(51000, CuaSoQuet::ketThuc(1000, 50000, 200000));
    }

    /**
     * Bat kip GIUA cua so: bo quet vua duoi kip trong luc cua so con dang mo. ketThuc phai
     * dung lai dung tai max(id) that, khong duoc chay tiep toi moc + cuaSo.
     */
    /** @test */
    public function bat_kip_giua_cua_so_thi_ket_thuc_la_max_id_that()
    {
        // moc=1000, cuaSo=50000 -> khong chan se la 51000, nhung maxIdThat=4607 (< 51000).
        $this->assertSame(4607, CuaSoQuet::ketThuc(1000, 50000, 4607));
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

    /**
     * Test tai lap CHINH XAC loi da tim thay o ban review: 4 luot day mot moc tu 4.407 len
     * 204.407 trong khi du lieu that chi toi 4.607, do khong chan cua so boi max(id) that.
     *
     * O day mo phong 5 luot LIEN TIEP tren mot bo quet DA BAT KIP duoi bang (khong con dong
     * moi nao sinh them trong luc quet - kich ban xau nhat: mocMoi phai dung yen tai
     * max(id) that, khong duoc chay tron.
     */
    /** @test */
    public function nam_luot_lien_tiep_da_bat_kip_khong_bao_gio_vuot_qua_max_id_that()
    {
        $maxIdThat = 4407; // khong co dong moi nao sinh them trong khi quet
        $cuaSo = 50000;
        $limit = 500;
        $moc = 4407; // da bat kip tu truoc luot dau tien

        for ($luot = 1; $luot <= 5; $luot++) {
            $cuoiCuaSo = CuaSoQuet::ketThuc($moc, $cuaSo, $maxIdThat);

            // Da bat kip: khong con dong nao > $moc de lay, nen lo luon rong.
            $soDongLay = 0;
            $maxIdTrongLo = 0;

            $moc = CuaSoQuet::mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo);

            $this->assertLessThanOrEqual(
                $maxIdThat, $moc,
                "Sau luot $luot, moc ($moc) da vuot qua max(id) that ($maxIdThat) - day chinh la loi mocmoi chay tron du lieu."
            );
        }

        $this->assertSame(4407, $moc, 'Sau 5 luot da bat kip, moc phai dung yen dung tai max(id) that.');
    }
}
