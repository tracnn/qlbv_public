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

        $s = PhongBi::chuoi('01234', ['XML1' => $xml1, 'XML2' => $xml2, 'XML3' => $xml3]);
        $x = simplexml_load_string($s);

        $this->assertNotFalse($x);
        $this->assertSame('01234', (string) $x->THONGTINDONVI->MACSKCB);
        $this->assertSame(date('Ymd'), (string) $x->THONGTINHOSO->NGAYLAP);
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

    public function test_sap_thu_tu_theo_so_bang()
    {
        $s = PhongBi::chuoi('01234', ['XML3' => XmlCon::chuoi('XML3', []), 'XML1' => XmlCon::chuoi('XML1', [['MA_LK' => '1']])]);
        $x = simplexml_load_string($s);
        $this->assertSame('XML1', (string) $x->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO[0]->LOAIHOSO);
    }
}
