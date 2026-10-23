<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\SoSanh\DocTepGiamDinh;
use Tests\TestCase;

class DocTepGiamDinhTest extends TestCase
{
    private function fileHoSo($loai, $noiDungThoXml)
    {
        return '<FILEHOSO><LOAIHOSO>' . $loai . '</LOAIHOSO><NOIDUNGFILE>' . $noiDungThoXml . '</NOIDUNGFILE></FILEHOSO>';
    }

    private function tep(array $fileHoSo)
    {
        return '<?xml version="1.0" encoding="utf-8"?><GIAMDINHHS><THONGTINDONVI><MACSKCB>01</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO><DANHSACHHOSO><HOSO>' . implode('', $fileHoSo) . '</HOSO></DANHSACHHOSO></THONGTINHOSO></GIAMDINHHS>';
    }

    public function test_doc_du_bang()
    {
        $xml1 = "\xEF\xBB\xBF" . '<XML1><MA_LK>LK1</MA_LK><HO_TEN>A</HO_TEN></XML1>';
        $xml2 = '<CHITIEU_CHITIET_THUOC><CHI_TIET_THUOC><MA_LK>LK1</MA_LK><MA_THUOC>T1</MA_THUOC></CHI_TIET_THUOC>'
            . '<CHI_TIET_THUOC><MA_LK>LK1</MA_LK><MA_THUOC>T2</MA_THUOC></CHI_TIET_THUOC></CHITIEU_CHITIET_THUOC>';
        $kq = (new DocTepGiamDinh())->doc($this->tep([
            $this->fileHoSo('xml1', base64_encode($xml1)),
            $this->fileHoSo('XML2', chunk_split(base64_encode($xml2), 20, "\n")),
        ]));

        $this->assertSame([], $kq['loi']);
        $this->assertSame([['MA_LK' => 'LK1', 'HO_TEN' => 'A']], $kq['bang']['XML1']);
        $this->assertCount(2, $kq['bang']['XML2']);
        $this->assertSame('T2', $kq['bang']['XML2'][1]['MA_THUOC']);
    }

    public function test_filehoso_hong_bao_loi_dung_bang()
    {
        $xml1 = '<XML1><MA_LK>LK1</MA_LK></XML1>';
        $kq = (new DocTepGiamDinh())->doc($this->tep([
            $this->fileHoSo('XML1', base64_encode($xml1)),
            $this->fileHoSo('XML2', '!!!khong phai base64!!!'),
            $this->fileHoSo('XML3', base64_encode('<CHITIEU_CHITIET_DVKT_VTYT><dang do')),
        ]));

        $this->assertArrayHasKey('XML1', $kq['bang']);
        $this->assertArrayNotHasKey('XML2', $kq['bang']);
        $this->assertArrayHasKey('XML2', $kq['loi']);
        $this->assertArrayHasKey('XML3', $kq['loi']);
        $this->assertArrayNotHasKey('XML1', $kq['loi']);
    }

    public function test_tep_hong_khong_nem_va_bao_loi_tep()
    {
        $kq = (new DocTepGiamDinh())->doc('khong phai xml');
        $this->assertSame([], $kq['bang']);
        $this->assertSame([], $kq['tho']);
        $this->assertSame(['TEP'], array_keys($kq['loi']));
        $this->assertNotSame('', $kq['loi']['TEP']);
    }

    public function test_tep_rong_cung_bao_loi_tep()
    {
        $kq = (new DocTepGiamDinh())->doc('');
        $this->assertArrayHasKey('TEP', $kq['loi']);
    }

    public function test_tra_xml_tho_tung_bang()
    {
        $xml1 = '<XML1><MA_LK>LK1</MA_LK></XML1>';
        $kq = (new DocTepGiamDinh())->doc($this->tep([$this->fileHoSo('XML1', base64_encode($xml1))]));
        $this->assertSame($xml1, $kq['tho']['XML1']);
        $this->assertArrayNotHasKey('TEP', $kq['loi']);
    }
}
