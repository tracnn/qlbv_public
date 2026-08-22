<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\Ct03;

/**
 * Canh registry loai chung tu: tra dung lop, va DOI CHIEU CHEO the goc.
 */
class CtdtLoaiRegistryTest extends TestCase
{
    /** @test */
    public function tra_duoc_lop_theo_gia_tri_loai_ho_so()
    {
        $this->assertSame(Ct03::class, CtdtLoaiRegistry::cho('CT03'));
        $this->assertTrue(CtdtLoaiRegistry::co('CT03'));
    }

    /** @test */
    public function loai_la_thi_nem_chu_khong_doan()
    {
        // Doan nghia la mot loai chung tu moi cua BHXH se bi nap vao SAI BANG ma khong
        // bao gi ca. Nem de nguoi van hanh biet ngay o buoc nap.
        $this->assertFalse(CtdtLoaiRegistry::co('CT99'));

        $this->expectException(\InvalidArgumentException::class);

        CtdtLoaiRegistry::cho('CT99');
    }

    /** @test */
    public function the_goc_khop_thi_khong_nem()
    {
        CtdtLoaiRegistry::xacNhanTheGoc('CT03', 'CT03');

        $this->assertTrue(true, 'Khop the goc thi khong duoc nem');
    }

    /** @test */
    public function the_goc_lech_thi_nem()
    {
        $this->expectException(\RuntimeException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('CT03', 'CT04');
    }

    /** @test */
    public function ct03_tu_mo_ta_dung_bang_va_model()
    {
        $this->assertSame('CT03', Ct03::maLoaiHoSo());
        $this->assertSame('CT03', Ct03::theGoc());
        $this->assertSame('CT2025', Ct03::dichVu());
        $this->assertSame('ctdt_ct03', Ct03::bang());
        $this->assertSame(\App\Models\BHYT\Ctdt\CtdtCt03::class, Ct03::model());
        $this->assertNotEmpty(Ct03::tenTab());
    }

    /** @test */
    public function ct03_anh_xa_the_sang_cot()
    {
        $truong = Ct03::truong();

        // Anh xa la THE -> COT, khong phai cot -> the: doc XML thi ta co ten the truoc.
        $this->assertSame('ma_yte', $truong['MA_YTE']);
        $this->assertSame('benhicd10_id', $truong['BENHICD10_ID']);
        $this->assertSame('tenbenhicd10', $truong['TENBENHICD10']);
        $this->assertSame('ghi_chu', $truong['GHI_CHU']);
        $this->assertCount(33, $truong, 'CT03 phai co dung 33 truong');
    }

    /** @test */
    public function ct03_lay_ma_chung_tu_tu_ma_yte()
    {
        $xml = simplexml_load_string('<CT03><MA_YTE>YT001</MA_YTE></CT03>');

        $this->assertSame('YT001', Ct03::maChungTu($xml));
    }

    /** @test */
    public function ct03_thieu_ma_yte_thi_tra_null()
    {
        $xml = simplexml_load_string('<CT03><MA_THE>DN123</MA_THE></CT03>');

        $this->assertNull(Ct03::maChungTu($xml));
    }

    /** @test */
    public function ct03_rut_gon_du_bay_cot_danh_sach()
    {
        $xml = simplexml_load_string(
            '<CT03><MA_THE>DN123</MA_THE><HO_TEN>Nguyen Van Test</HO_TEN>'
            . '<NGAY_SINH>19950914</NGAY_SINH><NGAY_VAO>201912121200</NGAY_VAO>'
            . '<NGAY_RA>201912180001</NGAY_RA>'
            . '<SO_CCCD>001095012345</SO_CCCD><MA_BHXH>0123456789</MA_BHXH></CT03>'
        );

        $this->assertSame([
            'ma_the'    => 'DN123',
            'ho_ten'    => 'Nguyen Van Test',
            'ngay_sinh' => '19950914',
            'ngay_vao'  => '201912121200',
            'ngay_ra'   => '201912180001',
            'so_cccd'   => '001095012345',
            'ma_bhxh'   => '0123456789',
        ], Ct03::rutGon($xml));
    }

    /** @test */
    public function rut_gon_the_rong_tra_null_khong_tra_chuoi_rong()
    {
        // Chuoi rong va "khong khai" la hai chuyen khac nhau khi doi soat voi BHXH.
        $xml = simplexml_load_string('<CT03><MA_THE></MA_THE></CT03>');

        $this->assertNull(Ct03::rutGon($xml)['ma_the']);
    }
}
