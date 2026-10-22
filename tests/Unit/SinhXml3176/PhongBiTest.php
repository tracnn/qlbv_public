<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\PhongBi;
use App\Services\SinhXml3176\XmlCon;
use PHPUnit\Framework\TestCase;

class PhongBiTest extends TestCase
{
    public function test_importer_doc_duoc_cau_truc()
    {
        $xml1 = XmlCon::chuoi('XML1', [['MA_LK' => '000123', 'HO_TEN' => 'Nguyen Van A', 'DIA_CHI' => 'So 1 <A> & B', 'MA_BN' => null]]);
        $xml2 = XmlCon::chuoi('XML2', [['MA_LK' => '000123', 'LIEU_DUNG' => "1 vien]]>x", 'MA_THUOC' => "a\x01b"]]);
        $xml3 = XmlCon::chuoi('XML3', [['MA_LK' => '000123', 'MA_DICH_VU' => 'K01']]);

        $s = PhongBi::chuoi('01234', ['XML1' => $xml1, 'XML2' => $xml2, 'XML3' => $xml3], new \DateTime('2026-10-22 15:30:00'));
        $x = simplexml_load_string($s);

        $this->assertNotFalse($x);
        $this->assertSame('01234', (string) $x->THONGTINDONVI->MACSKCB);
        $this->assertSame('20261022', (string) $x->THONGTINHOSO->NGAYLAP);
        $this->assertSame('1', (string) $x->THONGTINHOSO->SOLUONGHOSO);
        $this->assertCount(3, $x->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO);
        $this->assertSame('', trim((string) $x->CHUKYDONVI));

        $loai = [];
        foreach ($x->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $f) {
            $loai[] = (string) $f->LOAIHOSO;
            $raw = base64_decode((string) $f->NOIDUNGFILE);
            $this->assertNotSame("\xEF\xBB\xBF", substr($raw, 0, 3));
            $this->assertNotFalse(simplexml_load_string($raw));
        }
        $this->assertSame(['XML1', 'XML2', 'XML3'], $loai);

        $con1 = simplexml_load_string($xml1);
        $this->assertSame('TONG_HOP', $con1->getName());
        $this->assertSame('So 1 <A> & B', (string) $con1->DIA_CHI);
        $this->assertSame('', (string) $con1->MA_BN);
        $this->assertSame('1 vien]]>x', (string) simplexml_load_string($xml2)->DSACH_CHI_TIET_THUOC->CHI_TIET_THUOC->LIEU_DUNG);
        $this->assertSame('ab', (string) simplexml_load_string($xml2)->DSACH_CHI_TIET_THUOC->CHI_TIET_THUOC->MA_THUOC);
        $this->assertSame('CHITIEU_CHITIET_DVKT_VTYT', simplexml_load_string($xml3)->getName());
        $this->assertSame('K01', (string) simplexml_load_string($xml3)->DSACH_CHI_TIET_DVKT->CHI_TIET_DVKT->MA_DICH_VU);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="utf-8"?>', $xml1);
    }

    public function test_xml4_xml5_cau_truc_importer_doc_duoc()
    {
        $xml4 = XmlCon::chuoi('XML4', [['MA_LK' => '1', 'STT' => 1, 'MA_CHI_SO' => 'GLU', 'TEN_CHI_SO' => 'Glu', 'GIA_TRI' => '5,5', 'MO_TA' => "a]]>b", 'KET_LUAN' => 'ok', 'NGAY_KQ' => '202610010930']]);
        $xml5 = XmlCon::chuoi('XML5', [['MA_LK' => '1', 'STT' => 1, 'DIEN_BIEN_LS' => "x]]>y <z>", 'GIAI_DOAN_BENH' => 'On', 'HOI_CHAN' => '', 'PHAU_THUAT' => 'PT', 'THOI_DIEM_DBLS' => '202610010900']]);

        $a = simplexml_load_string($xml4);
        $this->assertSame('CHITIEU_CHITIET_DICHVUCANLAMSANG', $a->getName());
        $this->assertSame('a]]>b', (string) $a->DSACH_CHI_TIET_CLS->CHI_TIET_CLS->MO_TA);
        $this->assertSame('GLU', (string) $a->DSACH_CHI_TIET_CLS->CHI_TIET_CLS->MA_CHI_SO);
        $this->assertContains('<![CDATA[', $xml4);
        $this->assertNotContains('<![CDATA[GLU', $xml4);
        $b = simplexml_load_string($xml5);
        $this->assertSame('CHITIEU_CHITIET_DIENBIENLAMSANG', $b->getName());
        $this->assertSame('x]]>y <z>', (string) $b->DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH->DIEN_BIEN_LS);
        $this->assertSame('PT', (string) $b->DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH->PHAU_THUAT);

        $s = PhongBi::chuoi('01234', ['XML4' => $xml4, 'XML5' => $xml5], new \DateTime('2026-10-22'));
        $this->assertCount(2, simplexml_load_string($s)->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO);
    }

    public function test_sap_thu_tu_theo_so_bang()
    {
        $s = PhongBi::chuoi('01234', ['XML3' => XmlCon::chuoi('XML3', [['MA_LK' => '1']]), 'XML1' => XmlCon::chuoi('XML1', [['MA_LK' => '1']])], new \DateTime('2026-10-22'));
        $x = simplexml_load_string($s);
        $this->assertSame('XML1', (string) $x->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO[0]->LOAIHOSO);
    }
}
