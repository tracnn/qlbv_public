<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtTruongBatBuoc;
use App\Services\Ctdt\CtdtLoaiRegistry;
use Tests\Support\LocComment;

class CtdtTruongBatBuocTest extends TestCase
{
    use LocComment;

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
    public function MA_BHXH_bat_buoc_o_nhung_loai_co_can_cu()
    {
        // Cong van 2076/BHXH-CNTT PL02 danh dau "x" cho MA_BHXH o CT03, CT04, CT06, CT07 va
        // MA_BHXH_NND o CT05 (giay chung sinh). GIAYDIEUTRINOITRU khong nam trong cong van
        // do, nhung 923/923 chung tu that deu da co gia tri - du can cu de chan.
        foreach (['CT03', 'CT04', 'CT06', 'CT07', 'GIAYDIEUTRINOITRU'] as $loai) {
            $this->assertContains('MA_BHXH', CtdtTruongBatBuoc::cua($loai), $loai);
        }

        $this->assertContains('MA_BHXH_NND', CtdtTruongBatBuoc::cua('GIAYCHUNGSINH'));
    }

    /** @test */
    public function MA_BHXH_chi_CANH_BAO_o_ba_loai_chua_co_can_cu()
    {
        // GIAYDIEUTRIVOSINH, GIAYSUCKHOEME, GIAYBAOTU: cong van 2076 KHONG phu, va CSDL chua
        // co mot chung tu nao de doi chieu. Chan o day la doan mo.
        //
        // Rieng giay bao tu con mot kha nang that: nguoi qua doi co the khong co ma so BHXH.
        // Canh bao truoc, chi nang len muc chan khi du lieu that xac nhan.
        foreach (['GIAYDIEUTRIVOSINH', 'GIAYSUCKHOEME', 'GIAYBAOTU'] as $loai) {
            $this->assertNotContains('MA_BHXH', CtdtTruongBatBuoc::cua($loai),
                $loai . ': chua du can cu de CHAN');
            $this->assertContains('MA_BHXH', CtdtTruongBatBuoc::khuyenNghi($loai),
                $loai . ': phai it nhat canh bao');
        }
    }

    /** @test */
    public function MA_THE_khong_bat_buoc_VA_khong_khuyen_nghi()
    {
        // Rat nhieu benh nhan khong co the BHYT: tu tra, the het han, tre chua duoc cap the
        // (TEKT = 1). Cong van 2076 khong danh dau MA_THE bat buoc o loai nao.
        //
        // Cung KHONG de o muc khuyen nghi: canh bao tren mot tinh huong binh thuong la tieng
        // on, va no day nguoi van hanh toi cho bo qua ca cot so loi.
        foreach (array_keys(CtdtLoaiRegistry::tatCa()) as $loai) {
            foreach (['MA_THE', 'MA_THE_NND'] as $the) {
                $this->assertNotContains($the, CtdtTruongBatBuoc::cua($loai), $loai . '/' . $the);
                $this->assertNotContains($the, CtdtTruongBatBuoc::khuyenNghi($loai), $loai . '/' . $the);
            }
        }
    }

    /** @test */
    public function tang_khuyen_nghi_van_duoc_bo_kiem_hoi_den()
    {
        // Hien KHONG loai nao co truong khuyen nghi, nen khong test nao chay qua nhanh do
        // bang duong binh thuong. Giu tang nay vi buoc bo sung truong con thieu (xem tai
        // lieu module) se dung no de canh bao truoc roi moi chan.
        //
        // Neu ai do go loi goi khoi CtdtChecker, tang khuyen nghi se chet im lang va lan
        // them truong sau se tuong minh da bat dau canh bao trong khi khong co gi xay ra.
        $ma = $this->maKhongComment(base_path('app/Services/Ctdt/Kiem/CtdtChecker.php'));

        $this->assertContains('khuyenNghi(', $ma,
            'CtdtChecker phai con hoi CtdtTruongBatBuoc::khuyenNghi()');
    }

    /** @test */
    public function loai_la_tra_mang_rong_khong_nem()
    {
        // Loai la duoc CtdtChecker bo qua; nem o day se lam ca ho so hong vi mot loai
        // ma bo kiem chua biet den.
        $this->assertSame([], CtdtTruongBatBuoc::cua('KHONG_TON_TAI'));
        $this->assertSame([], CtdtTruongBatBuoc::khuyenNghi('KHONG_TON_TAI'));
    }

    /** @test */
    public function bo_sung_truong_bat_buoc_theo_cong_van_2076()
    {
        // Chi CHAN nhung truong ma du lieu that da du (do ngay 2026-08-20 tren 3047 chung tu):
        // rong 0% thi chan duoc ma khong khoa lai ho so nao.
        $mongDoi = [
            'CT03' => ['MA_KHOA', 'GIOI_TINH', 'DIA_CHI'],
            'CT04' => ['GIOI_TINH', 'DIA_CHI', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RA',
                       'QT_BENHLY', 'TOMTAT_KQ', 'TT_RAVIEN', 'NGAY_CT'],
            'CT07' => ['SO_KCB', 'GIOI_TINH', 'DON_VI', 'CHANDOAN_DIEUTRI',
                       'MA_CCHN', 'TEN_NGUOI_HANH_NGHE', 'TEKT'],
        ];

        foreach ($mongDoi as $loai => $cac) {
            foreach ($cac as $the) {
                $this->assertContains($the, CtdtTruongBatBuoc::cua($loai), $loai . '/' . $the);
            }
        }
    }

    /** @test */
    public function truong_du_lieu_chua_du_thi_chi_CANH_BAO()
    {
        // MA_DANTOC rong 6/1050, PP_DIEUTRI rong 79/1050 - chan se khoa lai dung nhung ho so
        // dang gui duoc. CT06 chua co mot chung tu nao de doi chieu, chan la doan mo.
        $mongDoi = [
            'CT04' => ['MA_DANTOC', 'PP_DIEUTRI'],
            'CT06' => ['SO_KCB', 'TEN_DVI', 'CHAN_DOAN', 'TEN_BS', 'MA_BS', 'NGAY_CT'],
        ];

        foreach ($mongDoi as $loai => $cac) {
            foreach ($cac as $the) {
                $this->assertContains($the, CtdtTruongBatBuoc::khuyenNghi($loai), $loai . '/' . $the);
                $this->assertNotContains($the, CtdtTruongBatBuoc::cua($loai),
                    $loai . '/' . $the . ': chua du can cu de CHAN');
            }
        }
    }
}
