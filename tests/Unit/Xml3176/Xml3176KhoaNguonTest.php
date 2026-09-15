<?php

namespace Tests\Unit\Xml3176;

use App\Services\Xml3176\Xml3176KhoaNguon;
use Tests\TestCase;

/**
 * Spec muc 5: khoa lay tu chinh dong khi bang co cot khoa (XML2, XML3 theo ma_lk+stt;
 * XML7 cot ma_khoa_rv theo ma_lk), con lai lay khoa ho so XML1.MA_KHOA.
 */
class Xml3176KhoaNguonTest extends TestCase
{
    /** @test */
    public function danh_sach_loai_xml_du_15_dung_thu_tu()
    {
        $this->assertSame(
            ['XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
             'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15'],
            Xml3176KhoaNguon::LOAI_XML
        );
    }

    /** @test */
    public function xml2_va_xml3_lay_ma_khoa_cua_dong_theo_ma_lk_va_stt()
    {
        $this->assertSame(
            ['bang' => 'xml3176_xml2s', 'cot' => 'ma_khoa', 'noiStt' => true],
            Xml3176KhoaNguon::nguon('XML2')
        );
        $this->assertSame(
            ['bang' => 'xml3176_xml3s', 'cot' => 'ma_khoa', 'noiStt' => true],
            Xml3176KhoaNguon::nguon('XML3')
        );
    }

    /** @test */
    public function xml7_lay_khoa_ra_vien_theo_ma_lk()
    {
        $this->assertSame(
            ['bang' => 'xml3176_xml7s', 'cot' => 'ma_khoa_rv', 'noiStt' => false],
            Xml3176KhoaNguon::nguon('XML7')
        );
    }

    /** @test */
    public function cac_loai_con_lai_dung_khoa_ho_so()
    {
        // XML1 chinh la bang khoa ho so; XML4 CO Y khong suy tu XML3 theo ma dich vu vi
        // mot ma dich vu co the o nhieu khoa trong cung ho so.
        foreach (['XML1', 'XML4', 'XML5', 'XML6', 'XML8', 'XML9', 'XML10', 'XML11',
                  'XML12', 'XML13', 'XML14', 'XML15', 'XMLComplete'] as $loai) {
            $this->assertNull(Xml3176KhoaNguon::nguon($loai), "$loai phai dung khoa ho so");
        }
    }

    /** @test */
    public function loai_la_dung_khoa_ho_so()
    {
        $this->assertNull(Xml3176KhoaNguon::nguon('XML99'));
        $this->assertNull(Xml3176KhoaNguon::nguon(''));
    }
}
