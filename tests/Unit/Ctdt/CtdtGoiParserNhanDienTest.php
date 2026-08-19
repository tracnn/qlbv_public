<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Nhan dien goi: dich vu nao, ma co so nao, bao nhieu ho so.
 */
class CtdtGoiParserNhanDienTest extends TestCase
{
    /** @test */
    public function xml_hong_thi_nem_chu_khong_tra_false()
    {
        // simplexml_load_string tra ve false va phat warning. De nguyen thi warning do
        // chui vao log dung dinh dang mot su co that, con false thi tro thanh loi
        // "Call to a member function on boolean" o tan dau do.
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::doc('<HSCHUNGTU><chua dong the');
    }

    /** @test */
    public function chuoi_rong_cung_nem()
    {
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::doc('');
    }

    /** @test */
    public function khoang_trang_dung_truoc_khai_bao_xml_van_doc_duoc()
    {
        // simplexml_load_string chiu duoc BOM UTF-8 nhung FAIL voi khoang trang/newline
        // dung truoc <?xml - va tep nguoi dung tai len rat de dinh dieu do.
        $goi = CtdtGoiParser::doc("\n  " . '<HSCHUNGTU/>');

        $this->assertSame('HSCHUNGTU', $goi->getName());
    }

    /** @test */
    public function nhan_dien_ba_dich_vu_theo_the_goc()
    {
        $bo = [
            'CT2025' => '<HSCHUNGTU/>',
            'GBT'    => '<HSDLGBT/>',
            'GCS'    => '<HSDLGCS/>',
        ];

        foreach ($bo as $mongDoi => $xml) {
            $this->assertSame($mongDoi, CtdtGoiParser::nhanDienDichVu(CtdtGoiParser::doc($xml)));
        }
    }

    /** @test */
    public function the_goc_la_thi_nem_chu_khong_doan()
    {
        // Doan nghia la mot dinh dang khac cua BHXH se bi nap vao SAI DICH VU va gui toi
        // SAI ENDPOINT ma khong bao gi ca.
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::nhanDienDichVu(CtdtGoiParser::doc('<GIAMDINHHS/>'));
    }

    /** @test */
    public function macskcb_cua_ct2025_lay_o_thongtindonvi()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI></HSCHUNGTU>'
        );

        $this->assertSame('01929', CtdtGoiParser::macskcb($goi, 'CT2025'));
    }

    /** @test */
    public function macskcb_cua_giay_bao_tu_lay_trong_the_giaybaotu()
    {
        $goi = CtdtGoiParser::doc('<HSDLGBT><GIAYBAOTU><MACSKCB>01013</MACSKCB></GIAYBAOTU></HSDLGBT>');

        $this->assertSame('01013', CtdtGoiParser::macskcb($goi, 'GBT'));
    }

    /** @test */
    public function giay_chung_sinh_khong_co_macskcb_nen_tra_null()
    {
        // PL02 phan IV khong khai MACSKCB o dau ca. MA_TTDV trong giong nhung muc 6 dinh
        // nghia la "ma so BHXH cua Thu truong co so KBCB" - ma cua mot CON NGUOI. Lay nham
        // no lam ma co so thi moi ho so GCS deu mang ma sai.
        $goi = CtdtGoiParser::doc('<HSDLGCS><GIAYCHUNGSINH><MA_TTDV>8901234567890</MA_TTDV></GIAYCHUNGSINH></HSDLGCS>');

        $this->assertNull(CtdtGoiParser::macskcb($goi, 'GCS'));
    }

    /** @test */
    public function macskcb_rong_coi_nhu_khong_co()
    {
        $goi = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINDONVI><MACSKCB>   </MACSKCB></THONGTINDONVI></HSCHUNGTU>');

        $this->assertNull(CtdtGoiParser::macskcb($goi, 'CT2025'));
    }

    /** @test */
    public function so_luong_ho_so_doc_gia_tri_that_khong_dem_node()
    {
        // Ban cu cua XML3176 dung count($node) - dem so phan tu CON nen LUON ra 1, bat ke
        // gia tri that. Loi do da tung lam mot tep khai 5 ho so duoc coi la khai 1.
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><SOLUONGHOSO>5</SOLUONGHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $this->assertSame(5, CtdtGoiParser::soLuongHoSo($goi));
    }

    /** @test */
    public function thieu_so_luong_ho_so_tra_khong()
    {
        $this->assertSame(0, CtdtGoiParser::soLuongHoSo(CtdtGoiParser::doc('<HSCHUNGTU/>')));
    }

    /** @test */
    public function ngay_lap_chi_co_o_ct2025()
    {
        $ct = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO><NGAYLAP>20251101</NGAYLAP></THONGTINHOSO></HSCHUNGTU>');

        $this->assertSame('20251101', CtdtGoiParser::ngayLap($ct));
        $this->assertNull(CtdtGoiParser::ngayLap(CtdtGoiParser::doc('<HSDLGBT/>')));
    }

    /** @test */
    public function id_goi_lay_dung_cho_tung_dich_vu()
    {
        $ct = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO Id="Id-abc"/></HSCHUNGTU>');
        $gbt = CtdtGoiParser::doc('<HSDLGBT><GIAYBAOTU Id="Id-def"/></HSDLGBT>');
        $gcs = CtdtGoiParser::doc('<HSDLGCS><GIAYCHUNGSINH Id="Id-ghi"/></HSDLGCS>');

        $this->assertSame('Id-abc', CtdtGoiParser::idGoi($ct, 'CT2025'));
        $this->assertSame('Id-def', CtdtGoiParser::idGoi($gbt, 'GBT'));
        $this->assertSame('Id-ghi', CtdtGoiParser::idGoi($gcs, 'GCS'));
    }

    /** @test */
    public function thieu_id_goi_tra_null_khong_nem()
    {
        // Thieu Id khong phai loi: chi lam mat duong lui cua khoa ho so, va cho do da co
        // ngoai le rieng.
        $this->assertNull(CtdtGoiParser::idGoi(CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO/></HSCHUNGTU>'), 'CT2025'));
    }
}
