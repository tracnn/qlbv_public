<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\DieuTriNoiTru;
use App\Services\Ctdt\Loai\DieuTriVoSinh;
use App\Services\Ctdt\Loai\SucKhoeMe;

/**
 * BAY CHINH CUA CA DAC TA: ba loai nay co gia tri LOAIHOSO KHAC ten the goc ben trong
 * base64. Neu lay ten the goc lam khoa tra thi chung roi vao nhanh "loai la" IM LANG.
 */
class CtdtLoaiLechTenTest extends TestCase
{
    public function baLoaiLech()
    {
        return [
            ['GIAYDIEUTRINOITRU', 'CTGiayDieuTriNoiTru', DieuTriNoiTru::class, 'ctdt_dieu_tri_noi_tru', 36],
            ['GIAYDIEUTRIVOSINH', 'CTGiayDieuTriVoSinh', DieuTriVoSinh::class, 'ctdt_dieu_tri_vo_sinh', 29],
            ['GIAYSUCKHOEME',     'CTGiaySucKhoeMe',     SucKhoeMe::class,     'ctdt_suc_khoe_me', 29],
        ];
    }

    /** @test */
    public function ba_loai_khai_the_goc_KHAC_gia_tri_loai_ho_so()
    {
        foreach ($this->baLoaiLech() as list($loaiHoSo, $theGoc, $lop, $bang, $soTruong)) {
            $this->assertSame($loaiHoSo, $lop::maLoaiHoSo());
            $this->assertSame($theGoc, $lop::theGoc());
            $this->assertNotSame($lop::maLoaiHoSo(), $lop::theGoc(),
                $loaiHoSo . ': the goc phai KHAC gia tri LOAIHOSO');
            $this->assertSame('CT2025', $lop::dichVu());
            $this->assertSame($bang, $lop::bang());
            $this->assertCount($soTruong, $lop::truong(), $loaiHoSo . ': sai so truong');
        }
    }

    /** @test */
    public function doi_chieu_cheo_chap_nhan_the_goc_dung()
    {
        foreach ($this->baLoaiLech() as list($loaiHoSo, $theGoc, $_lop, $_bang, $_so)) {
            CtdtLoaiRegistry::xacNhanTheGoc($loaiHoSo, $theGoc);
        }

        $this->assertTrue(true, 'The goc dung thi khong duoc nem');
    }

    /** @test */
    public function doi_chieu_cheo_bat_duoc_khi_noi_dung_la_ten_loai_ho_so()
    {
        // Loi de xay ra nhat: phan mem sinh XML dat ten the goc bang chinh gia tri
        // LOAIHOSO. Phai bat, khong duoc nap im lang.
        $this->expectException(\RuntimeException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('GIAYDIEUTRINOITRU', 'GIAYDIEUTRINOITRU');
    }

    /** @test */
    public function ba_loai_deu_lay_ma_chung_tu_tu_ma_yte()
    {
        foreach ($this->baLoaiLech() as list($_loai, $theGoc, $lop, $_bang, $_so)) {
            $xml = simplexml_load_string('<' . $theGoc . '><MA_YTE>YT777</MA_YTE></' . $theGoc . '>');

            $this->assertSame('YT777', $lop::maChungTu($xml));
        }
    }

    /** @test */
    public function noi_tru_giu_ten_the_khong_deu_theo_dac_ta()
    {
        $truong = DieuTriNoiTru::truong();

        // MA_DAN_TOC co gach duoi, khac CT03 (MA_DANTOC). BENH_ICD10_MA la MA khong phai ID.
        $this->assertSame('ma_dan_toc', $truong['MA_DAN_TOC']);
        $this->assertSame('benh_icd10_ma', $truong['BENH_ICD10_MA']);
        $this->assertArrayNotHasKey('MA_DANTOC', $truong);
        $this->assertArrayNotHasKey('BENH_ICD10_ID', $truong);
    }

    /** @test */
    public function vo_sinh_va_suc_khoe_me_khong_co_gioi_tinh()
    {
        // Ca hai loai deu chi ap dung cho lao dong nu nen dac ta khong khai GIOI_TINH.
        // Cot rut gon van phai chay duoc, khong duoc nem vi thieu the.
        foreach ([DieuTriVoSinh::class, SucKhoeMe::class] as $lop) {
            $this->assertArrayNotHasKey('GIOI_TINH', $lop::truong());
        }
    }

    /** @test */
    public function rut_gon_chay_duoc_ca_khi_thieu_the()
    {
        $xml = simplexml_load_string('<CTGiaySucKhoeMe><HO_TEN>Test</HO_TEN></CTGiaySucKhoeMe>');

        $rutGon = SucKhoeMe::rutGon($xml);

        $this->assertSame('Test', $rutGon['ho_ten']);
        $this->assertNull($rutGon['ma_the']);
        $this->assertNull($rutGon['ngay_vao']);
    }
}
