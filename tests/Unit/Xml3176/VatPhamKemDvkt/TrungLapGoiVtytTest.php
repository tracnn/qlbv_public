<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026: GOI_VTYT_KHONG_KHOP nhanh "DVKT me khong co trong ho so" trung voi
 * XML3_DATE_MISMATCH (Xml3176Xml3Checker) - ben do da bao chinh dong VTYT do.
 */
class TrungLapGoiVtytTest extends TestCase
{
    private function dv($ma, $ngay = '202610050800', $maVatTu = '')
    {
        return (object) ['ma_dich_vu' => $ma, 'ma_vat_tu' => $maVatTu, 'ten_dich_vu' => 'DVKT', 't_bhtt' => 100000, 'so_luong' => 1, 'ngay_yl' => $ngay];
    }

    private function vt($maDv, $maNhom = 10, $ngay = '202610050900')
    {
        return (object) ['ma_dich_vu' => $maDv, 'ma_vat_tu' => 'N06.02.020.0549.274.0004', 'ten_vat_tu' => 'Giá đỡ',
            'goi_vtyt' => 'G1', 't_bhtt' => 29000000, 'so_luong' => 1, 'ngay_yl' => $ngay, 'ma_nhom' => $maNhom];
    }

    private function keys(array $xml3): array
    {
        return array_column((new VatPhamKemDvktRule([]))->kiem($xml3, []), 'key');
    }

    /** @test */
    public function vtyt_nhom_vtyt_khong_co_dvkt_me_thi_nhuong_xml3_date_mismatch()
    {
        $this->assertSame([], $this->keys([$this->vt('18.0659.0054')]));
    }

    /** @test */
    public function vtyt_ngoai_nhom_vtyt_thi_xml3_khong_xet_nen_van_bao()
    {
        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys([$this->vt('18.0659.0054', 4)]));
    }

    /** @test */
    public function dvkt_khac_hoa_thuong_cung_ngay_thi_xml3_khop_nen_van_bao()
    {
        // MySQL so '=' khong phan biet hoa thuong: XML3 tim thay DVKT cung ngay, khong bao.
        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys([$this->dv('AB.01'), $this->vt('ab.01')]));
    }

    /** @test */
    public function mot_dong_trong_nhom_ngoai_nhom_vtyt_thi_van_bao()
    {
        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys([$this->vt('18.0659.0054'), $this->vt('18.0659.0054', 4)]));
    }
}
