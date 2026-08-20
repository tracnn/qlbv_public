<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtQuyTac;
use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Canh bang kieu truong. Khoa theo TEN THE va dung chung cho ca chin loai - giong cach
 * CtdtNhanTruong da lam, vi ten the lap lai rat nhieu giua cac loai.
 */
class CtdtQuyTacTest extends TestCase
{
    /** @test */
    public function nhan_dien_truong_ngay()
    {
        foreach (['NGAY_SINH', 'NGAY_VAO', 'NGAY_RA', 'NGAYCAP_CCCD', 'NGAY_TV',
                  'NGAYGIO_VV', 'TU_NGAY', 'DEN_NGAY', 'NGAY_SINH_CON', 'NGAYSINH_NND'] as $the) {
            $this->assertSame('ngay', CtdtQuyTac::kieuCua($the), $the . ' phai la truong ngay');
        }
    }

    /** @test */
    public function SO_NGAY_NGHIDUONGTHAI_KHONG_phai_truong_ngay()
    {
        // Day la ly do bang nay phai liet ke TUONG MINH thay vi doan theo ten: mot the
        // chua chu NGAY nhung la SO NGAY, doan theo ten se bat no phai la 'YYYYMMDD'.
        $this->assertNotSame('ngay', CtdtQuyTac::kieuCua('SO_NGAY_NGHIDUONGTHAI'));
    }

    /** @test */
    public function TUOI_THAI_va_SO_CON_khong_phai_truong_ngay()
    {
        $this->assertNull(CtdtQuyTac::kieuCua('TUOI_THAI'));
        $this->assertNull(CtdtQuyTac::kieuCua('SO_CON'));
    }

    /** @test */
    public function nhan_dien_gioi_tinh_va_loai_giay_to()
    {
        $this->assertSame('gioi_tinh', CtdtQuyTac::kieuCua('GIOI_TINH'));
        $this->assertSame('gioi_tinh', CtdtQuyTac::kieuCua('GIOI_TINH_CON'));

        foreach (['LOAI_GIAYTO', 'LOAI_GIAYTO_NND', 'LOAI_GIAYTO_MTH',
                  'LOAI_GIAYTO_CHA_MTH', 'LOAI_GIAYTO_CHA_NND'] as $the) {
            $this->assertSame('loai_giayto', CtdtQuyTac::kieuCua($the), $the);
        }
    }

    /** @test */
    public function nhan_dien_truong_co()
    {
        foreach (['TEKT', 'DINH_CHI_THAI_NGHEN', 'IS_NOI_KHOA', 'IS_NGHIDUONGTHAI',
                  'SINHCON_PHAUTHUAT', 'SINHCON_DUOI32TUAN', 'CAP_LAN_DAU'] as $the) {
            $this->assertSame('co_khong', CtdtQuyTac::kieuCua($the), $the);
        }
    }

    /** @test */
    public function the_khong_co_quy_tac_tra_null()
    {
        $this->assertNull(CtdtQuyTac::kieuCua('CHAN_DOAN'));
        $this->assertNull(CtdtQuyTac::kieuCua('THE_MOI_TINH'));
    }

    /** @test */
    public function moi_the_trong_bang_deu_ton_tai_o_it_nhat_mot_loai()
    {
        // Mot the khai trong bang quy tac ma khong loai nao dung nghia la go sai ten - va
        // quy tac do se khong bao gio chay, im lang.
        $coThat = [];

        foreach (CtdtLoaiRegistry::tatCa() as $lop) {
            foreach (array_keys($lop::truong()) as $the) {
                $coThat[$the] = true;
            }
        }

        foreach (array_keys(CtdtQuyTac::BANG) as $the) {
            $this->assertArrayHasKey($the, $coThat,
                'The "' . $the . '" khai trong CtdtQuyTac nhung khong loai nao co');
        }
    }

    /** @test */
    public function moi_cap_ngay_deu_ton_tai_o_it_nhat_mot_loai()
    {
        $coThat = [];

        foreach (CtdtLoaiRegistry::tatCa() as $lop) {
            foreach (array_keys($lop::truong()) as $the) {
                $coThat[$the] = true;
            }
        }

        foreach (CtdtQuyTac::CAP_NGAY as $dau => $cuoi) {
            $this->assertArrayHasKey($dau, $coThat, 'The bat dau "' . $dau . '" khong loai nao co');
            $this->assertArrayHasKey($cuoi, $coThat, 'The ket thuc "' . $cuoi . '" khong loai nao co');
        }
    }

    /** @test */
    public function danh_muc_ma_loi_du_tam_ma_va_deu_co_muc_do_hop_le()
    {
        $danhMuc = config('ctdt.ma_loi');

        $this->assertCount(8, $danhMuc);

        foreach (['CTDT001', 'CTDT002', 'CTDT003', 'CTDT004',
                  'CTDT005', 'CTDT006', 'CTDT007', 'CTDT008'] as $ma) {
            $this->assertArrayHasKey($ma, $danhMuc, 'Thieu ma loi ' . $ma);
            $this->assertNotEmpty($danhMuc[$ma]['mo_ta'], $ma . ' thieu mo ta');
            $this->assertContains($danhMuc[$ma]['muc_do'], ['chan', 'canh_bao'], $ma . ' sai muc do');
        }
    }

    /** @test */
    public function muc_do_khop_dung_thiet_ke()
    {
        $danhMuc = config('ctdt.ma_loi');

        // Chan: thieu truong bat buoc, ngay sai, gioi tinh sai, loai giay to sai, ngay
        // nguoc, ma co so lech. Canh bao: co 0/1 sai, thieu ma the.
        foreach (['CTDT001', 'CTDT002', 'CTDT003', 'CTDT004', 'CTDT006', 'CTDT007'] as $ma) {
            $this->assertSame('chan', $danhMuc[$ma]['muc_do'], $ma . ' phai la muc chan');
        }

        foreach (['CTDT005', 'CTDT008'] as $ma) {
            $this->assertSame('canh_bao', $danhMuc[$ma]['muc_do'], $ma . ' phai la muc canh bao');
        }
    }

    /** @test */
    public function ma_the_o_muc_canh_bao_vi_tre_em_khong_the_la_hop_le()
    {
        // PL02 co the TEKT (tre em khong the) voi gia tri 1 la hop le. Dat MA_THE o muc
        // chan se chan nham moi ho so tre so sinh - dung nhom ma giay chung sinh phuc vu.
        $this->assertSame('canh_bao', config('ctdt.ma_loi.CTDT008.muc_do'));
    }
}
