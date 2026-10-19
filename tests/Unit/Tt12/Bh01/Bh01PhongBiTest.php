<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01DacTa;
use App\Services\Tt12\Bh01\Bh01PhongBi;
use Tests\TestCase;

class Bh01PhongBiTest extends TestCase
{
    private function dong($stt, $ghiDe = array())
    {
        $d = array(
            'stt' => (string) $stt, 'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000',
            'gioi_tinh' => '1', 'ma_the_bhyt' => 'DN4010000000001', 'ma_benh_chinh' => 'E11.4',
            'ngay_vao' => '202609010800', 'ngay_vao_noi_tru' => '', 'ngay_ra' => '202609051000',
            'so_ngay_dtri' => '4', 'ma_loai_kcb' => '01', 't_tongchi_bv' => '1000.5',
            't_tongchi_bh' => '900', 't_bhtt' => '700', 't_bncct' => '200', 't_bntt' => '0',
            't_nguonkhac' => '0', 'ma_cskcb' => '01001', 'nam_qt' => '2026', 'thang_qt' => '09',
        );
        return array_merge($d, $ghiDe);
    }

    private function chiTiet($d)
    {
        $x = '<CHITIET_HS01BH>';
        foreach ($d as $k => $v) {
            $t = strtoupper($k);
            $x .= $v === '' ? "<$t/>" : "<$t>$v</$t>";
        }
        return $x . '</CHITIET_HS01BH>';
    }

    /** @test */
    public function dung_dung_tung_byte()
    {
        $d1 = $this->dong(1);
        $d2 = $this->dong(2, array('ngay_vao_noi_tru' => '202609011000'));
        $mongDoi = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<HSTH01BH><DS_CHITIET Id="Id-x">' . $this->chiTiet($d1) . $this->chiTiet($d2)
            . '</DS_CHITIET><CHUKYDONVI/></HSTH01BH>';
        $this->assertSame($mongDoi, Bh01PhongBi::dung('Id-x', array($d1, $d2)));
        $this->assertContains('<NGAY_VAO_NOI_TRU/>', $mongDoi);
        $this->assertSame(20, count(Bh01DacTa::the()));
    }

    /** @test */
    public function chap_nhan_stdclass_va_thieu_khoa_thanh_the_rong()
    {
        $o = (object) array('stt' => '1', 'ho_ten' => 'X');
        $xml = Bh01PhongBi::dung('Id-x', new \ArrayIterator(array($o)));
        $this->assertContains('<STT>1</STT><HO_TEN>X</HO_TEN><NGAY_SINH/>', $xml);
    }

    /** @test */
    public function thoat_ky_tu_dac_biet()
    {
        $xml = Bh01PhongBi::dung('Id-x', array($this->dong(1, array('ho_ten' => 'A & B <C>'))));
        $this->assertContains('A &amp; B &lt;C&gt;', $xml);
        $doc = simplexml_load_string($xml);
        $this->assertSame('A & B <C>', (string) $doc->DS_CHITIET->CHITIET_HS01BH->HO_TEN);
    }

    /** @test */
    public function giu_nguyen_dau_dao_icd()
    {
        $xml = Bh01PhongBi::dung('Id-x', array($this->dong(1, array('ma_benh_chinh' => "E11.4\u{2020}"))));
        $doc = simplexml_load_string($xml);
        $this->assertSame("E11.4\u{2020}", (string) $doc->DS_CHITIET->CHITIET_HS01BH->MA_BENH_CHINH);
    }

    /** @test */
    public function goc_khong_co_namespace_xsd_xsi()
    {
        $xml = Bh01PhongBi::dung('Id-x', array($this->dong(1)));
        $this->assertNotContains('xmlns', $xml);
        $this->assertNotContains('xsi', $xml);
        $this->assertNotContains('xsd', $xml);
    }

    /** @test */
    public function cung_dau_vao_cung_dau_ra()
    {
        $dong = array($this->dong(1), $this->dong(2));
        $this->assertSame(Bh01PhongBi::dung('Id-x', $dong), Bh01PhongBi::dung('Id-x', $dong));
    }

    /** @test */
    public function khong_co_dong_thi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01PhongBi::dung('Id-x', array());
    }

    /** @test */
    public function khong_co_dong_tu_generator_thi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01PhongBi::dung('Id-x', call_user_func(function () {
            return;
            yield 1;
        }));
    }

    /** @test */
    public function thieu_id_thi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01PhongBi::dung('', array($this->dong(1)));
    }
}
