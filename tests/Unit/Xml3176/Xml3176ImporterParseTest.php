<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176Importer;

class Xml3176ImporterParseTest extends TestCase
{
    private function importer()
    {
        return app(Xml3176Importer::class);
    }

    /** @test */
    public function chuoi_khong_parse_duoc_thi_that_bai_co_ly_do()
    {
        $kq = $this->importer()->nhapTuChuoi('day khong phai xml <<<');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }

    /** @test */
    public function chuoi_rong_thi_that_bai_co_ly_do()
    {
        $kq = $this->importer()->nhapTuChuoi('');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }

    /** @test */
    public function thieu_macskcb_thi_that_bai_co_ly_do()
    {
        $xml = '<GIAMDINHHS><THONGTINDONVI></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>1</SOLUONGHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('MACSKCB', $kq->lyDoThatBai);
    }

    /** @test */
    public function macskcb_rong_cung_bi_coi_la_thieu()
    {
        $xml = '<GIAMDINHHS><THONGTINDONVI><MACSKCB></MACSKCB></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>1</SOLUONGHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('MACSKCB', $kq->lyDoThatBai);
    }

    /** @test */
    public function khong_co_filehoso_nao_thi_that_bai_khong_no()
    {
        // Khong co FILEHOSO -> khong co ma_lk -> khong duoc coi la thanh cong,
        // va tuyet doi khong duoc nem loi.
        $xml = '<GIAMDINHHS><THONGTINDONVI><MACSKCB>01234</MACSKCB></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>0</SOLUONGHOSO>'
             . '<DANHSACHHOSO></DANHSACHHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }
}
