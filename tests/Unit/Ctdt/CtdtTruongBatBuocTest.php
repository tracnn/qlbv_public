<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtTruongBatBuoc;
use App\Services\Ctdt\CtdtLoaiRegistry;

class CtdtTruongBatBuocTest extends TestCase
{
    /** @test */
    public function moi_loai_deu_co_danh_sach_bat_buoc_khong_rong()
    {
        foreach (array_keys(CtdtLoaiRegistry::tatCa()) as $loai) {
            $this->assertNotEmpty(CtdtTruongBatBuoc::cua($loai),
                $loai . ' phai co it nhat mot truong bat buoc');
        }
    }

    /** @test */
    public function moi_the_bat_buoc_deu_ton_tai_trong_truong_cua_loai_do()
    {
        // Bat buoc mot the ma loai do khong co nghia la MOI ho so loai do deu bao loi -
        // va khong ai sua duoc, vi the do khong bao gio ton tai.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $truong = $lop::truong();

            foreach (CtdtTruongBatBuoc::cua($loai) as $the) {
                $this->assertArrayHasKey($the, $truong,
                    $loai . ': the bat buoc "' . $the . '" khong co trong truong()');
            }

            foreach (CtdtTruongBatBuoc::khuyenNghi($loai) as $the) {
                $this->assertArrayHasKey($the, $truong,
                    $loai . ': the khuyen nghi "' . $the . '" khong co trong truong()');
            }
        }
    }

    /** @test */
    public function khoa_nghiep_vu_la_bat_buoc_o_nhung_loai_co_no()
    {
        $this->assertContains('MA_GBT', CtdtTruongBatBuoc::cua('GIAYBAOTU'));
        $this->assertContains('MA_GCS', CtdtTruongBatBuoc::cua('GIAYCHUNGSINH'));
    }

    /** @test */
    public function MA_YTE_KHONG_bat_buoc_o_bat_ky_loai_nao()
    {
        // Cong van 2076/BHXH-CNTT, Phu luc 02, muc 3.4 (bang truong cua CT03): cot "Bat buoc"
        // cua MA_YTE BO TRONG, va dien giai ghi ro:
        //
        //   "Ma y te dinh danh chung tu cua cskcb, DE TRONG DE HE THONG BHXH TU SINH
        //    (chi nen su dung 1 cach)"
        //
        // De trong la DUNG dac ta, khong phai loi. Truoc day ta bat buoc no, va dieu do chan
        // 97% ho so that (1049/1050 CT03 va 923/923 GIAYDIEUTRINOITRU deu co the MA_YTE nhung
        // gia tri rong) - phan mem sinh XML lam dung, quy tac cua ta moi sai.
        //
        // Xac nhan bang lan gui that dau tien: MaGD cong tra ve la HS_CHUNGTU01929_<GUID>,
        // dung la ma BHXH tu sinh.
        foreach (array_keys(CtdtLoaiRegistry::tatCa()) as $loai) {
            $this->assertNotContains('MA_YTE', CtdtTruongBatBuoc::cua($loai),
                $loai . ': MA_YTE khong duoc la truong bat buoc');
        }
    }

    /** @test */
    public function MA_YTE_cung_KHONG_nam_o_muc_khuyen_nghi()
    {
        // Khong ha xuong canh bao: dac ta cho phep de trong nhu mot trong hai cach dung hop
        // le. Canh bao tren gan nhu MOI ho so la mot bien canh bao vo nghia, va nguoi van
        // hanh se hoc cach bo qua ca cot so loi - dung dieu docblock cua lop nay canh bao.
        foreach (array_keys(CtdtLoaiRegistry::tatCa()) as $loai) {
            $this->assertNotContains('MA_YTE', CtdtTruongBatBuoc::khuyenNghi($loai), $loai);
        }
    }

    /** @test */
    public function ho_ten_va_ngay_sinh_bat_buoc_o_moi_loai()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $batBuoc = CtdtTruongBatBuoc::cua($loai);
            $truong = $lop::truong();

            $coHoTen = array_key_exists('HO_TEN', $truong) ? 'HO_TEN' : 'HOTEN_NND';
            $coNgaySinh = array_key_exists('NGAY_SINH', $truong) ? 'NGAY_SINH' : 'NGAYSINH_NND';

            $this->assertContains($coHoTen, $batBuoc, $loai . ' phai bat buoc ho ten');
            $this->assertContains($coNgaySinh, $batBuoc, $loai . ' phai bat buoc ngay sinh');
        }
    }

    /** @test */
    public function ma_the_o_muc_khuyen_nghi_khong_phai_bat_buoc()
    {
        // Tre em khong the (TEKT = 1) la hop le va khong co MA_THE. Dat o muc bat buoc se
        // chan nham dung nhom ma giay chung sinh phuc vu.
        $this->assertNotContains('MA_THE', CtdtTruongBatBuoc::cua('CT03'));
        $this->assertContains('MA_THE', CtdtTruongBatBuoc::khuyenNghi('CT03'));
    }

    /** @test */
    public function loai_la_tra_mang_rong_khong_nem()
    {
        // Loai la duoc CtdtChecker bo qua; nem o day se lam ca ho so hong vi mot loai
        // ma bo kiem chua biet den.
        $this->assertSame([], CtdtTruongBatBuoc::cua('KHONG_TON_TAI'));
        $this->assertSame([], CtdtTruongBatBuoc::khuyenNghi('KHONG_TON_TAI'));
    }
}
