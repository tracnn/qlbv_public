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

    public function test_xml6_den_15_cau_truc_importer_doc_duoc()
    {
        // [loai => [the goc, the danh sach|null, the ban ghi|null, truong CDATA, truong thuong]]
        $mau = [
            'XML6' => ['CHI_TIEU_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS', 'DSACH_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS', 'HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS', 'DIA_CHI', 'SO_CCCD'],
            'XML7' => ['CHI_TIEU_DU_LIEU_GIAY_RA_VIEN', null, null, 'PP_DIEUTRI', 'CHAN_DOAN_RV'],
            'XML8' => ['CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN', null, null, 'KET_QUA_DTRI', 'GHI_CHU'],
            'XML9' => ['CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH', 'DSACH_GIAYCHUNGSINH', 'DU_LIEU_GIAY_CHUNG_SINH', 'HO_TEN_CON', 'SO'],
            'XML10' => ['CHI_TIEU_DU_LIEU_GIAY_NGHI_DUONG_THAI', null, null, 'DON_VI', 'SO_CT'],
            'XML11' => ['CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI', null, null, 'HO_TEN_ME', 'SO_CT'],
            'XML13' => ['CHI_TIEU_GIAYCHUYENTUYEN', null, null, 'HOTEN_NGUOI_HT', 'MA_THE_BHYT'],
            'XML14' => ['CHI_TIEU_GIAYHEN_KHAMLAI', null, null, 'CHAN_DOAN_RV', 'MA_THE_BHYT'],
            'XML15' => ['CHI_TIEU_DIEUTRI_BENHLAO', 'DSACH_CHITIET_DIEUTRI_BENHLAO', 'CHITIET_DIEUTRI_BENHLAO', 'HO_TEN', 'SO_CCCD'],
        ];
        $con = [];
        foreach ($mau as $loai => $m) {
            $dong = ['MA_LK' => 'LK1', $m[3] => 'a]]>b <x>', $m[4] => '123'];
            $xml = XmlCon::chuoi($loai, [$dong]);
            $con[$loai] = $xml;
            $x = simplexml_load_string($xml);
            $this->assertNotFalse($x, $loai);
            $this->assertSame($m[0], $x->getName(), $loai);
            $r = $m[1] === null ? $x : $x->{$m[1]}->{$m[2]};
            $this->assertSame('LK1', (string) $r->MA_LK, $loai);
            $this->assertSame('a]]>b <x>', (string) $r->{$m[3]}, $loai);
            $this->assertSame('123', (string) $r->{$m[4]}, $loai);
            $this->assertContains('<![CDATA[', $xml, $loai);
            $this->assertNotContains('<![CDATA[123', $xml, $loai);
        }
        // CDATA theo tung loai: XML7/XML8 khong boc CHAN_DOAN_RV, GHI_CHU nhu danh sach chung cua XML1-3
        $this->assertNotContains('CDATA', XmlCon::chuoi('XML7', [['MA_LK' => 'L', 'CHAN_DOAN_RV' => 'x']]));
        $this->assertNotContains('CDATA', XmlCon::chuoi('XML8', [['MA_LK' => 'L', 'GHI_CHU' => 'x']]));
        $this->assertContains('CDATA', XmlCon::chuoi('XML1', [['MA_LK' => 'L', 'GHI_CHU' => 'x']]));
        // Hai ban ghi o bang danh sach
        $hai = simplexml_load_string(XmlCon::chuoi('XML9', [['MA_LK' => '1'], ['MA_LK' => '2']]));
        $this->assertCount(2, $hai->DSACH_GIAYCHUNGSINH->DU_LIEU_GIAY_CHUNG_SINH);

        $s = PhongBi::chuoi('01234', $con, new \DateTime('2026-10-22'));
        $this->assertCount(9, simplexml_load_string($s)->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO);
    }

    public function test_sap_thu_tu_theo_so_bang()
    {
        $s = PhongBi::chuoi('01234', ['XML3' => XmlCon::chuoi('XML3', [['MA_LK' => '1']]), 'XML1' => XmlCon::chuoi('XML1', [['MA_LK' => '1']])], new \DateTime('2026-10-22'));
        $x = simplexml_load_string($s);
        $this->assertSame('XML1', (string) $x->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO[0]->LOAIHOSO);
    }

    /**
     * HIS ghi xuong dong nguyen van nen bo doc XML doi CRLF/CR thanh LF; XMLWriter::text lai thoat CR thanh &#13;
     * nen CR con nguyen sau khi nap. Nghiem thu Dot 3: XML7 GHI_CHU, XML8 QT_BENHLY lech chi vi CR.
     */
    public function test_xuong_dong_doc_lai_giong_his()
    {
        $xml8 = XmlCon::chuoi('XML8', [['MA_LK' => '1', 'QT_BENHLY' => "a\r\nb\rc\nd", 'TOMTAT_KQ' => "x\r\ny"]]);
        $x = simplexml_load_string($xml8);

        $this->assertNotContains('&#13;', $xml8);
        $this->assertSame("a\nb\nc\nd", (string) $x->QT_BENHLY);
        $this->assertSame("x\ny", (string) $x->TOMTAT_KQ);
    }
}
