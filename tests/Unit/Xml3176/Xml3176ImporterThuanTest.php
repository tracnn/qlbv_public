<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176Importer;

class Xml3176ImporterThuanTest extends TestCase
{
    private function xml($ben_trong)
    {
        return simplexml_load_string('<GIAMDINHHS>' . $ben_trong . '</GIAMDINHHS>');
    }

    /** @test */
    public function so_luong_ho_so_doc_dung_gia_tri_that()
    {
        // Ban cu dung count() tren node la nen LUON ra 1, bat ke gia tri that.
        $x = $this->xml('<THONGTINHOSO><SOLUONGHOSO>37</SOLUONGHOSO></THONGTINHOSO>');

        $this->assertSame(37, Xml3176Importer::soLuongHoSo($x));
    }

    /** @test */
    public function so_luong_ho_so_tra_khong_khi_thieu_hoac_rong()
    {
        $this->assertSame(0, Xml3176Importer::soLuongHoSo($this->xml('<THONGTINHOSO></THONGTINHOSO>')));
        $this->assertSame(0, Xml3176Importer::soLuongHoSo($this->xml('')));
        $this->assertSame(0, Xml3176Importer::soLuongHoSo(
            $this->xml('<THONGTINHOSO><SOLUONGHOSO></SOLUONGHOSO></THONGTINHOSO>')
        ));
    }

    /** @test */
    public function sap_xml1_len_dau_va_giu_thu_tu_tuong_doi_phan_con_lai()
    {
        // deleteExistingXml3176 chi chay khi gap XML1. Neu XML2 duoc ghi truoc do thi
        // no bi xoa ngay sau - im lang.
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML3', 'XML1', 'XML5']);

        $this->assertEquals([2, 0, 1, 3], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_giu_nguyen_khi_xml1_da_dung_dau()
    {
        $kq = Xml3176Importer::sapXml1LenDau(['XML1', 'XML2', 'XML3']);

        $this->assertEquals([0, 1, 2], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_giu_nguyen_khi_khong_co_xml1()
    {
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML3']);

        $this->assertEquals([0, 1], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_khong_no_voi_mang_rong()
    {
        $this->assertEquals([], Xml3176Importer::sapXml1LenDau([]));
    }

    /** @test */
    public function sap_xml1_len_dau_chi_dua_xml1_dau_tien_len()
    {
        // File di thuong co hai XML1: van chi mot cai len dau, cai kia giu thu tu cu.
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML1', 'XML1']);

        $this->assertEquals([1, 0, 2], $kq);
    }
}
