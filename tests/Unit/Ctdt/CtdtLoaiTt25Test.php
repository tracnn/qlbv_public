<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\Ct04;
use App\Services\Ctdt\Loai\Ct06;
use App\Services\Ctdt\Loai\Ct07;

/**
 * Ba loai co ten the goc TRUNG voi gia tri LOAIHOSO, va deu KHONG co MA_YTE.
 */
class CtdtLoaiTt25Test extends TestCase
{
    public function baLoai()
    {
        return [
            ['CT04', Ct04::class, 'ctdt_ct04', 45],
            ['CT06', Ct06::class, 'ctdt_ct06', 24],
            ['CT07', Ct07::class, 'ctdt_ct07', 27],
        ];
    }

    /** @test */
    public function ba_loai_nam_trong_registry_va_thuoc_dich_vu_ct2025()
    {
        foreach ($this->baLoai() as list($ma, $lop, $bang, $soTruong)) {
            $this->assertSame($lop, CtdtLoaiRegistry::cho($ma));
            $this->assertSame($ma, $lop::maLoaiHoSo());
            $this->assertSame($ma, $lop::theGoc(), $ma . ': the goc phai trung LOAIHOSO');
            $this->assertSame('CT2025', $lop::dichVu());
            $this->assertSame($bang, $lop::bang());
            $this->assertCount($soTruong, $lop::truong(), $ma . ': sai so truong');
            $this->assertNotEmpty($lop::tenTab());
        }
    }

    /** @test */
    public function ba_loai_deu_khong_co_ma_yte_nen_ma_chung_tu_la_null()
    {
        // Day chinh la ly do ho so chi gom CT04/CT06/CT07 roi vao nhanh lui GUID va
        // KHONG ghi de duoc. Ghim hanh vi nay bang test de khong ai "sua" thanh doan bua.
        foreach ($this->baLoai() as list($ma, $lop, $_bang, $_so)) {
            $xml = simplexml_load_string('<' . $ma . '><MA_THE>DN123</MA_THE></' . $ma . '>');

            $this->assertNull($lop::maChungTu($xml), $ma . ': khong duoc bia ma chung tu');
        }
    }

    /** @test */
    public function ct04_dung_ngay_ct_khong_phai_ngay_chung_tu()
    {
        $truong = Ct04::truong();

        $this->assertSame('ngay_ct', $truong['NGAY_CT']);
        $this->assertArrayNotHasKey('NGAY_CHUNG_TU', $truong, 'CT04 khong co the NGAY_CHUNG_TU');
    }

    /** @test */
    public function ct07_dung_tu_ngay_den_ngay_lam_khoang_dieu_tri()
    {
        // CT07 khong co NGAY_VAO/NGAY_RA ma dung TU_NGAY/DEN_NGAY. Cot rut gon van phai
        // day du de man danh sach loc duoc theo mot bo cot duy nhat.
        $xml = simplexml_load_string(
            '<CT07><MA_THE>DN123</MA_THE><HO_TEN>Test</HO_TEN>'
            . '<NGAY_SINH>19950914</NGAY_SINH><TU_NGAY>20251001</TU_NGAY>'
            . '<DEN_NGAY>20251010</DEN_NGAY></CT07>'
        );

        $rutGon = Ct07::rutGon($xml);

        $this->assertSame('20251001', $rutGon['ngay_vao']);
        $this->assertSame('20251010', $rutGon['ngay_ra']);
    }

    /** @test */
    public function ct06_rut_gon_doc_dung_the()
    {
        $xml = simplexml_load_string(
            '<CT06><MA_THE>DN456</MA_THE><HO_TEN>Tran Thi Test</HO_TEN>'
            . '<NGAY_SINH>19480826</NGAY_SINH><NGAY_VAO>20251003</NGAY_VAO>'
            . '<NGAY_RA>20251030</NGAY_RA>'
            . '<SO_CCCD>001048067890</SO_CCCD><MA_BHXH>9876543210</MA_BHXH></CT06>'
        );

        $this->assertSame([
            'ma_the'    => 'DN456',
            'ho_ten'    => 'Tran Thi Test',
            'ngay_sinh' => '19480826',
            'ngay_vao'  => '20251003',
            'ngay_ra'   => '20251030',
            'so_cccd'   => '001048067890',
            'ma_bhxh'   => '9876543210',
        ], Ct06::rutGon($xml));
    }
}
