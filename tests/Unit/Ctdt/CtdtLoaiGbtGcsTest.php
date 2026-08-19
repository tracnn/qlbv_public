<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\GiayBaoTu;
use App\Services\Ctdt\Loai\GiayChungSinh;

/**
 * Hai loai thuoc HAI dich vu rieng (loaiHs 60 va 61), khac bay loai TT25 (loaiHs 39).
 */
class CtdtLoaiGbtGcsTest extends TestCase
{
    /** @test */
    public function giay_bao_tu_thuoc_dich_vu_gbt()
    {
        $this->assertSame('GIAYBAOTU', GiayBaoTu::maLoaiHoSo());
        $this->assertSame('GIAYBAOTU', GiayBaoTu::theGoc());
        $this->assertSame('GBT', GiayBaoTu::dichVu());
        $this->assertSame('ctdt_giay_bao_tu', GiayBaoTu::bang());
        $this->assertCount(38, GiayBaoTu::truong());
    }

    /** @test */
    public function giay_chung_sinh_thuoc_dich_vu_gcs()
    {
        $this->assertSame('GIAYCHUNGSINH', GiayChungSinh::maLoaiHoSo());
        $this->assertSame('GIAYCHUNGSINH', GiayChungSinh::theGoc());
        $this->assertSame('GCS', GiayChungSinh::dichVu());
        $this->assertSame('ctdt_giay_chung_sinh', GiayChungSinh::bang());
        $this->assertCount(69, GiayChungSinh::truong());
    }

    /** @test */
    public function khoa_nghiep_vu_lay_tu_ma_gbt_va_ma_gcs()
    {
        // Hai loai nay LUON co khoa nghiep vu, khac CT04/CT06/CT07. Nghia la ho so
        // GBT/GCS luon ghi de duoc khi nap lai.
        $gbt = simplexml_load_string('<GIAYBAOTU><MA_GBT>00002.GBT.XXXX.25</MA_GBT></GIAYBAOTU>');
        $gcs = simplexml_load_string('<GIAYCHUNGSINH><MA_GCS>00005.GCS.XXXXX.25</MA_GCS></GIAYCHUNGSINH>');

        $this->assertSame('00002.GBT.XXXX.25', GiayBaoTu::maChungTu($gbt));
        $this->assertSame('00005.GCS.XXXXX.25', GiayChungSinh::maChungTu($gcs));
    }

    /** @test */
    public function giay_bao_tu_rut_gon_dung_ngay_gio_vv_va_ngay_tv()
    {
        // Giay bao tu khong co NGAY_RA. Ngay tu vong dong vai tro ket thuc dot dieu tri.
        $xml = simplexml_load_string(
            '<GIAYBAOTU><MA_THE>GD497</MA_THE><HO_TEN>Nguyen Van Test</HO_TEN>'
            . '<NGAY_SINH>20220101</NGAY_SINH><NGAYGIO_VV>202510070100</NGAYGIO_VV>'
            . '<NGAY_TV>202510070200</NGAY_TV></GIAYBAOTU>'
        );

        $rutGon = GiayBaoTu::rutGon($xml);

        $this->assertSame('202510070100', $rutGon['ngay_vao']);
        $this->assertSame('202510070200', $rutGon['ngay_ra']);
    }

    /** @test */
    public function giay_chung_sinh_rut_gon_lay_thong_tin_NGUOI_ME_khong_phai_con()
    {
        // Man danh sach tra cuu theo nguoi co the BHYT - la nguoi me, khong phai dua con.
        $xml = simplexml_load_string(
            '<GIAYCHUNGSINH><MA_THE_NND>GD123</MA_THE_NND><HOTEN_NND>Pham Minh Test</HOTEN_NND>'
            . '<NGAYSINH_NND>20000220</NGAYSINH_NND><TEN_CON>Test Test</TEN_CON>'
            . '<NGAY_SINH_CON>20251021000000</NGAY_SINH_CON></GIAYCHUNGSINH>'
        );

        $rutGon = GiayChungSinh::rutGon($xml);

        $this->assertSame('GD123', $rutGon['ma_the']);
        $this->assertSame('Pham Minh Test', $rutGon['ho_ten']);
        $this->assertSame('20000220', $rutGon['ngay_sinh']);
        $this->assertSame('20251021000000', $rutGon['ngay_vao'], 'Ngay sinh con dai 14 ky tu');
    }

    /** @test */
    public function giay_chung_sinh_phan_biet_ba_nhom_hau_to()
    {
        $truong = GiayChungSinh::truong();

        $this->assertSame('hoten_nnd', $truong['HOTEN_NND']);
        $this->assertSame('hoten_mth', $truong['HOTEN_MTH']);
        $this->assertSame('ho_ten_cha_mth', $truong['HO_TEN_CHA_MTH']);
        $this->assertSame('so_cccd_cha_nnd', $truong['SO_CCCD_CHA_NND']);
    }

    /** @test */
    public function registry_du_chin_loai_va_chia_dung_ba_dich_vu()
    {
        $this->assertCount(9, CtdtLoaiRegistry::tatCa(), 'Phai co dung chin loai chung tu');

        $this->assertCount(7, CtdtLoaiRegistry::cuaDichVu('CT2025'), 'CT2025 co bay loai');
        $this->assertCount(1, CtdtLoaiRegistry::cuaDichVu('GBT'));
        $this->assertCount(1, CtdtLoaiRegistry::cuaDichVu('GCS'));
    }
}
