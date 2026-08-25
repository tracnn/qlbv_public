<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12PhongBi;
use App\Services\Tt12\Tt12MauRegistry;

class Tt12PhongBiTest extends TestCase
{
    private function dongMau01(array $ghiDe = array())
    {
        return array('du_lieu' => array_merge(array(
            'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
            'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
            'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
        ), $ghiDe), 'con' => array());
    }

    private function dung($mau, array $cacDong, $id = 'Id-abc-123')
    {
        $lop = Tt12MauRegistry::cho($mau);

        return Tt12PhongBi::dung($lop, $id, $cacDong);
    }

    private function doc($xml)
    {
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'XML sinh ra khong parse duoc');

        return $doc;
    }

    /** @test */
    public function the_goc_va_hai_namespace_dung_theo_tai_lieu()
    {
        $doc = $this->doc($this->dung('MAU_01', array($this->dongMau01())));
        $goc = $doc->documentElement;

        $this->assertSame('HSDANHMUC', $goc->nodeName);
        $this->assertSame('http://www.w3.org/2001/XMLSchema', $goc->getAttribute('xmlns:xsd'));
        $this->assertSame('http://www.w3.org/2001/XMLSchema-instance', $goc->getAttribute('xmlns:xsi'));
    }

    /** @test */
    public function the_danh_sach_mang_dung_thuoc_tinh_Id()
    {
        // Chu ky XMLDSig tham chieu #Id nay. Mat Id la chu ky khong tro vao dau va cong
        // tra 205.
        $doc = $this->doc($this->dung('MAU_01', array($this->dongMau01()), 'Id-cu-the'));

        $ds = $doc->getElementsByTagName('DANHSACH_DMBOPHANCHUYENMON')->item(0);

        $this->assertNotNull($ds);
        $this->assertSame('Id-cu-the', $ds->getAttribute('Id'));
    }

    /** @test */
    public function the_trong_mot_dong_dung_thu_tu_dac_ta()
    {
        // Du lieu vao phai DAO NGUOC so voi Mau01::tenThe(), khong duoc giu nguyen thu tu
        // dac ta. dongMau01() vo tinh viet dung y het thu tu do, nen neu dung du lieu goc,
        // test nay van xanh ke ca khi cai dat sai (vi du lap qua array_keys($du_lieu) thay
        // vi $lop::tenThe()) - hai thu tu tinh co trung nhau. DAO NGUOC de test chi xanh khi
        // cai dat that su lay thu tu tu dac ta. DUNG "don gian hoa" lai thanh du lieu thuan -
        // se lam mat tac dung phat hien cua test.
        $duLieuDao = array_reverse($this->dongMau01()['du_lieu'], true);

        $doc = $this->doc($this->dung('MAU_01', array(array('du_lieu' => $duLieuDao, 'con' => array()))));
        $dong = $doc->getElementsByTagName('DMBOPHANCHUYENMON')->item(0);

        $ten = array();

        foreach ($dong->childNodes as $con) {
            if ($con->nodeType === XML_ELEMENT_NODE) {
                $ten[] = $con->nodeName;
            }
        }

        $lop = Tt12MauRegistry::cho('MAU_01');

        $this->assertSame($lop::tenThe(), $ten);
    }

    /** @test */
    public function gia_tri_so_0_van_in_ra_noi_dung_con_chuoi_rong_moi_la_the_rong()
    {
        // Neu cai dat doi tu "$giaTri !== ''" sang "if ($giaTri)" (falsy check), gia tri
        // so '0' se bi coi la rong va in ra <GIUONG_PD/> thay vi <GIUONG_PD>0</GIUONG_PD> -
        // mat du lieu that gui len cong: mot khoa khai 0 giuong khac han mot khoa khong khai
        // so giuong. Dat ca hai truong hop trong cung mot bai de thay ro su khac biet.
        $doc = $this->doc($this->dung('MAU_01', array($this->dongMau01(array('GIUONG_PD' => '0', 'DEN_NGAY' => '')))));

        $giuongPd = $doc->getElementsByTagName('GIUONG_PD')->item(0);
        $denNgay = $doc->getElementsByTagName('DEN_NGAY')->item(0);

        $this->assertSame('0', $giuongPd->textContent, 'Gia tri so 0 phai duoc in ra, khong duoc coi la rong');
        $this->assertFalse($giuongPd->childNodes->length === 0, 'The mang gia tri 0 khong duoc la the rong');
        $this->assertTrue($denNgay->childNodes->length === 0, 'The mang chuoi rong moi la the rong');
    }

    /** @test */
    public function o_rong_van_giu_the_nhung_the_khong_co_noi_dung()
    {
        // Tai lieu in <DEN_NGAY/> chu khong bo the. Bo the la doi cau truc XML gui len.
        $doc = $this->doc($this->dung('MAU_01', array($this->dongMau01())));
        $den = $doc->getElementsByTagName('DEN_NGAY')->item(0);

        $this->assertNotNull($den, 'Khong duoc bo the rong');
        $this->assertSame('', $den->textContent);
    }

    /** @test */
    public function ky_tu_dac_biet_trong_ten_khong_pha_vo_tai_lieu()
    {
        // Ten nha san xuat, ten khoa den tu Excel nguoi dung. Mot dau & la du pha vo ca
        // tai lieu neu noi chuoi thay vi dung DOMDocument.
        $xml = $this->dung('MAU_01', array(
            $this->dongMau01(array('TEN_KHOA' => 'Khám bệnh & Cấp cứu <Khu A> "mới"')),
        ));

        $doc = $this->doc($xml);
        $ten = $doc->getElementsByTagName('TEN_KHOA')->item(0);

        $this->assertSame('Khám bệnh & Cấp cứu <Khu A> "mới"', $ten->textContent);
    }

    /** @test */
    public function chukydonvi_la_the_rong_va_nam_cuoi_cung()
    {
        // XMLSignService GHI chu ky vao mot the DA TON TAI
        // (tag_store_signature_value = 'CHUKYDONVI'), khong tu tao the.
        $doc = $this->doc($this->dung('MAU_01', array($this->dongMau01())));
        $goc = $doc->documentElement;

        $conCuoi = null;

        foreach ($goc->childNodes as $con) {
            if ($con->nodeType === XML_ELEMENT_NODE) {
                $conCuoi = $con;
            }
        }

        $this->assertSame('CHUKYDONVI', $conCuoi->nodeName);
        $this->assertFalse($conCuoi->hasChildNodes(), 'CHUKYDONVI phai rong truoc khi ky');
    }

    /** @test */
    public function nhieu_dong_deu_duoc_in_ra()
    {
        $xml = $this->dung('MAU_01', array(
            $this->dongMau01(array('STT' => '1', 'MA_KHOA' => 'K01')),
            $this->dongMau01(array('STT' => '2', 'MA_KHOA' => 'K02')),
            $this->dongMau01(array('STT' => '3', 'MA_KHOA' => 'K03')),
        ));

        $doc = $this->doc($xml);

        $this->assertSame(3, $doc->getElementsByTagName('DMBOPHANCHUYENMON')->length);
    }

    /** @test */
    public function mau_04_va_mau_06_dung_tien_to_DSACH_chu_khong_DANHSACH_()
    {
        $xml4 = $this->dung('MAU_04', array(array('du_lieu' => array('STT' => '1', 'MA_VAT_TU' => 'VT1'), 'con' => array())));
        $xml6 = $this->dung('MAU_06', array(array('du_lieu' => array('STT' => '1', 'TEN_TB' => 'Máy thở'), 'con' => array())));

        $this->assertContains('<DSACH_TBYT ', $xml4);
        $this->assertNotContains('DANHSACH_TBYT', $xml4);
        $this->assertContains('<DM_TBYT>', $xml4);

        $this->assertContains('<DSACH_TBYTTHDV ', $xml6);
        $this->assertContains('<DM_TBYTTHDV>', $xml6);
    }

    /** @test */
    public function mau_05_long_dung_DS_THUOCPX_trong_dong_cha()
    {
        $dong = array(
            'du_lieu' => array('STT' => '1', 'MA_DICH_VU' => 'DV01', 'TEN_DICH_VU' => 'Xạ hình'),
            'con' => array(
                array('stt' => '1', 'ma_thuoc' => 'TPX01', 'ten_thuoc' => 'Tc-99m',
                      'don_gia_thuoc' => '120000'),
            ),
        );

        $doc = $this->doc($this->dung('MAU_05', array($dong)));

        $ds = $doc->getElementsByTagName('DS_THUOCPX');
        $this->assertSame(1, $ds->length);

        // DS_THUOCPX phai nam BEN TRONG DMDICHVUKBCB, khong phai anh em cua no.
        $this->assertSame('DMDICHVUKBCB', $ds->item(0)->parentNode->nodeName);

        $tt = $doc->getElementsByTagName('TT_THUOCPX');
        $this->assertSame(1, $tt->length);
        $this->assertSame('DS_THUOCPX', $tt->item(0)->parentNode->nodeName);

        $ma = $doc->getElementsByTagName('MA_THUOC')->item(0);
        $this->assertSame('TPX01', $ma->textContent);
    }

    /** @test */
    public function mau_05_khong_co_thuoc_phong_xa_thi_khong_in_DS_THUOCPX()
    {
        $dong = array(
            'du_lieu' => array('STT' => '1', 'MA_DICH_VU' => 'DV02', 'TEN_DICH_VU' => 'Siêu âm'),
            'con' => array(),
        );

        $doc = $this->doc($this->dung('MAU_05', array($dong)));

        $this->assertSame(0, $doc->getElementsByTagName('DS_THUOCPX')->length,
            'Dich vu khong co thuoc phong xa khong duoc mang the DS_THUOCPX rong');
    }

    /** @test */
    public function ho_so_khong_co_dong_nao_bi_tu_choi()
    {
        // Mot phong bi rong van duoc cong nhan va tra ve MaGD, va ta se tuong da gui
        // thanh cong mot danh muc von khong co gi ben trong.
        $this->expectException(\InvalidArgumentException::class);

        $this->dung('MAU_01', array());
    }

    /** @test */
    public function thieu_id_danh_sach_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->dung('MAU_01', array($this->dongMau01()), '');
    }
}
