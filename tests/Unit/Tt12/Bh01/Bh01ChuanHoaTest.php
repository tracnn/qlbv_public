<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01ChuanHoa;
use Tests\TestCase;

class Bh01ChuanHoaTest extends TestCase
{
    /** @test */
    public function tien_hai_chu_so_thap_phan_dau_cham()
    {
        $this->assertSame('1234.5', Bh01ChuanHoa::tien(1234.5));
        $this->assertSame('1234.57', Bh01ChuanHoa::tien(1234.567));
        $this->assertSame('1000', Bh01ChuanHoa::tien(1000));
        $this->assertSame('0', Bh01ChuanHoa::tien(null));
        $this->assertSame('0.3', Bh01ChuanHoa::tien(0.1 + 0.2));
    }

    private function xml1(array $ghiDe = array())
    {
        return (object) array_merge(array(
            'ho_ten' => 'Nguyen Van A', 'ngay_sinh' => '198001010000', 'gioi_tinh' => '1',
            'ma_the_bhyt' => 'GD4010000000001', 'ma_benh_chinh' => 'E11.4',
            'ngay_vao' => '202610010800', 'ngay_vao_noi_tru' => '202610010900', 'ngay_ra' => '202610051000',
            'so_ngay_dtri' => 4, 'ma_loai_kcb' => '03',
            't_tongchi_bv' => 1000, 't_tongchi_bh' => 800.5, 't_bhtt' => 700, 't_bncct' => 100,
            't_bntt' => 200, 't_nguonkhac' => null,
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09',
        ), $ghiDe);
    }

    /** @test */
    public function lay_the_dau_tien_khi_nhieu_the()
    {
        $d = Bh01ChuanHoa::dong($this->xml1(array('ma_the_bhyt' => 'GD4010000000001;GD4010000000002')));
        $this->assertSame('GD4010000000001', $d['ma_the_bhyt']);
        $this->assertCount(19, $d);
        $this->assertSame('0', $d['t_nguonkhac']);
        $this->assertSame('800.5', $d['t_tongchi_bh']);
        $this->assertSame('4', $d['so_ngay_dtri']);
        $this->assertSame('09', $d['thang_qt']);
        $this->assertSame('03', $d['ma_loai_kcb']);
    }

    /** @test */
    public function ngay_vao_noi_tru_rong_giu_chuoi_rong()
    {
        $d = Bh01ChuanHoa::dong($this->xml1(array('ngay_vao_noi_tru' => null)));
        $this->assertSame('', $d['ngay_vao_noi_tru']);
    }

    /** @test */
    public function cac_truong_con_lai_nguyen_van()
    {
        $d = Bh01ChuanHoa::dong(array_merge((array) $this->xml1(), array(
            'ho_ten' => 'Nguyễn Văn A & B', 'ma_benh_chinh' => 'E11.4†',
        )));
        $this->assertSame('Nguyễn Văn A & B', $d['ho_ten']);
        $this->assertSame('E11.4†', $d['ma_benh_chinh']);
    }
}
