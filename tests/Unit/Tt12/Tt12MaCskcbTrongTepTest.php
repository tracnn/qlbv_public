<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12MaCskcbTrongTep;

class Tt12MaCskcbTrongTepTest extends TestCase
{
    /** Ba cot gia: [0] = STT, [1] = ten, [2] = MA_CSKCB */
    private function lo(array $maCoSo)
    {
        $lo = array();

        foreach ($maCoSo as $i => $ma) {
            $lo[] = array($i + 1, 'Khoa ' . ($i + 1), $ma);
        }

        return $lo;
    }

    /** @test */
    public function moi_dong_trung_co_so_thi_khong_co_gi_lech()
    {
        $kq = Tt12MaCskcbTrongTep::kiem($this->lo(array('01929', '01929')), 2, '01929', 1);

        $this->assertSame(array(), $kq['lech']);
        $this->assertSame(0, $kq['so_o_trong']);
    }

    /** @test */
    public function dong_lech_duoc_bao_kem_so_thu_tu_dung()
    {
        // sttBatDau = 11 mo phong lo thu hai cua mot tep lon: so thu tu bao ve phai la so
        // trong CA TEP, khong phai vi tri trong lo.
        $kq = Tt12MaCskcbTrongTep::kiem($this->lo(array('01929', '37470', '01929')), 2, '01929', 11);

        $this->assertCount(1, $kq['lech']);
        $this->assertSame(12, $kq['lech'][0]['stt']);
        $this->assertSame('37470', $kq['lech'][0]['gia_tri']);
    }

    /** @test */
    public function o_trong_duoc_dem_rieng_chu_khong_tinh_la_lech()
    {
        $kq = Tt12MaCskcbTrongTep::kiem($this->lo(array('', '01929', '')), 2, '01929', 1);

        $this->assertSame(array(), $kq['lech']);
        $this->assertSame(2, $kq['so_o_trong']);
    }

    /** @test */
    public function khoang_trang_thua_khong_lam_dong_thanh_lech()
    {
        $kq = Tt12MaCskcbTrongTep::kiem($this->lo(array(' 01929 ')), 2, ' 01929', 1);

        $this->assertSame(array(), $kq['lech']);
    }

    /** @test */
    public function tep_khong_co_cot_MA_CSKCB_thi_moi_dong_tinh_la_o_trong()
    {
        $kq = Tt12MaCskcbTrongTep::kiem($this->lo(array('x', 'y')), null, '01929', 1);

        $this->assertSame(array(), $kq['lech']);
        $this->assertSame(2, $kq['so_o_trong']);
    }

    /** @test */
    public function mo_ta_liet_ke_toi_da_hai_muoi_dong_va_noi_ro_con_bao_nhieu()
    {
        $lech = array();

        for ($i = 1; $i <= 25; $i++) {
            $lech[] = array('stt' => $i, 'gia_tri' => '37470');
        }

        $moTa = Tt12MaCskcbTrongTep::moTaLech($lech, '01929');

        $this->assertContains('25 dòng', $moTa);
        $this->assertContains('dòng 20 = "37470"', $moTa);
        $this->assertNotContains('dòng 21 =', $moTa, 'Chi liet ke toi da 20 dong');
        $this->assertContains('và 5 dòng khác', $moTa);
    }
}
