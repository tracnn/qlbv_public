<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01Kiem;
use Tests\TestCase;

class Bh01KiemTest extends TestCase
{
    private function dong(array $ghiDe = array())
    {
        return array_merge(array(
            'ho_ten' => 'Nguyen Van A', 'ngay_sinh' => '198001010000', 'gioi_tinh' => '1',
            'ma_the_bhyt' => 'GD4010000000001', 'ma_benh_chinh' => 'E11.4',
            'ngay_vao' => '202610010800', 'ngay_vao_noi_tru' => '', 'ngay_ra' => '202610051000',
            'so_ngay_dtri' => '4', 'ma_loai_kcb' => '03',
            't_tongchi_bv' => '1000', 't_tongchi_bh' => '800.5', 't_bhtt' => '700', 't_bncct' => '100',
            't_bntt' => '200', 't_nguonkhac' => '0',
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09',
        ), $ghiDe);
    }

    /** @test */
    public function dong_hop_le_khong_chan_khong_canh_bao()
    {
        $kq = Bh01Kiem::dong($this->dong());
        $this->assertSame(array(), $kq['chan']);
        $this->assertSame(array(), $kq['canh_bao']);
    }

    /** @test */
    public function thieu_truong_bat_buoc_thi_chan()
    {
        foreach (array('ho_ten', 'ma_the_bhyt', 'ngay_vao', 'ngay_ra', 'ma_loai_kcb', 'ma_cskcb') as $cot) {
            $kq = Bh01Kiem::dong($this->dong(array($cot => '')));
            $this->assertNotEmpty($kq['chan'], $cot);
        }
    }

    /** @test */
    public function ngay_khong_du_12_chu_so_thi_chan()
    {
        $kq = Bh01Kiem::dong($this->dong(array('ngay_ra' => '20261001')));
        $this->assertNotEmpty($kq['chan']);
    }

    /** @test */
    public function ngay_sinh_rong_chi_canh_bao()
    {
        $kq = Bh01Kiem::dong($this->dong(array('ngay_sinh' => '')));
        $this->assertSame(array(), $kq['chan']);
        $this->assertCount(1, $kq['canh_bao']);
        $this->assertContains('NGAY_SINH', $kq['canh_bao'][0]);
    }

    /** @test */
    public function ngay_vao_noi_tru_rong_khong_chan()
    {
        $kq = Bh01Kiem::dong($this->dong(array('ngay_vao_noi_tru' => '')));
        $this->assertSame(array(), $kq['chan']);
    }

    /** @test */
    public function tien_khong_phai_so_thi_chan()
    {
        $kq = Bh01Kiem::dong($this->dong(array('t_bhtt' => 'abc')));
        $this->assertNotEmpty($kq['chan']);
    }

    /** @test */
    public function ma_loai_kcb_ngoai_01_09_thi_chan()
    {
        $this->assertNotEmpty(Bh01Kiem::dong($this->dong(array('ma_loai_kcb' => '10')))['chan']);
        $this->assertNotEmpty(Bh01Kiem::dong($this->dong(array('ma_loai_kcb' => '1')))['chan']);
        $this->assertSame(array(), Bh01Kiem::dong($this->dong(array('ma_loai_kcb' => '09')))['chan']);
    }

    /** @test */
    public function the_17_ky_tu_chi_canh_bao()
    {
        $kq = Bh01Kiem::dong($this->dong(array('ma_the_bhyt' => 'HC438038070001032')));
        $this->assertSame(array(), $kq['chan']);
        $this->assertCount(1, $kq['canh_bao']);
        $this->assertContains('MA_THE_BHYT', $kq['canh_bao'][0]);
    }

    /** @test */
    public function ma_benh_co_dau_dao_khong_bi_bao_dai()
    {
        $kq = Bh01Kiem::dong($this->dong(array('ma_benh_chinh' => 'E11.4†')));
        $this->assertSame(array(), $kq['canh_bao']);
    }
}
