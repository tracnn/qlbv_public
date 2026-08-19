<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtImportResult;
use App\Services\Ctdt\CtdtImportFileResult;

class CtdtImportResultTest extends TestCase
{
    /** @test */
    public function ket_qua_mot_ho_so_thanh_cong()
    {
        $kq = CtdtImportResult::thanhCong('YT001', ['CT03', 'CT04']);

        $this->assertTrue($kq->thanhCong);
        $this->assertSame('YT001', $kq->maHoSo);
        $this->assertSame(['CT03', 'CT04'], $kq->loaiDaXuLy);
        $this->assertNull($kq->lyDoThatBai);
    }

    /** @test */
    public function ket_qua_mot_ho_so_that_bai_giu_ly_do()
    {
        // Tra doi tuong thay vi bool: giao dien can ly do CU THE de hien, chu khong phai
        // mot cau chung chung nhu "cau truc khong hop le".
        $kq = CtdtImportResult::thatBai('Thieu MA_YTE');

        $this->assertFalse($kq->thanhCong);
        $this->assertSame('Thieu MA_YTE', $kq->lyDoThatBai);
        $this->assertNull($kq->maHoSo);
    }

    /** @test */
    public function gop_ket_qua_tat_ca_thanh_cong()
    {
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thanhCong('YT002', ['CT03']),
        ], 2, 2);

        $this->assertTrue($kq->thanhCong);
        $this->assertNull($kq->lyDoThatBai);
        $this->assertSame(2, $kq->soThanhCong);
        $this->assertSame(0, $kq->soThatBai);
        $this->assertSame(['YT001', 'YT002'], $kq->dsMaHoSo);
    }

    /** @test */
    public function gop_ket_qua_co_ho_so_hong_thi_neu_ro_ho_so_thu_may()
    {
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thatBai('The goc lech'),
        ], 2, 2);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(1, $kq->soThanhCong);
        $this->assertSame(1, $kq->soThatBai);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);
        $this->assertContains('The goc lech', $kq->lyDoThatBai);
        $this->assertSame(['YT001'], $kq->dsMaHoSo, 'Ho so hong khong duoc vao danh sach thanh cong');
    }

    /** @test */
    public function thuc_te_it_hon_khai_bao_thi_tu_choi_ca_tep()
    {
        // Tep co the bi cat cut. Nhap mot phan roi bao thanh cong la kieu hong nguy hiem
        // nhat: khong ai biet phan con thieu ton tai.
        $kq = CtdtImportFileResult::tu([CtdtImportResult::thanhCong('YT001', ['CT03'])], 3, 1);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('SOLUONGHOSO', $kq->lyDoThatBai);
        $this->assertContains('3', $kq->lyDoThatBai);
        $this->assertContains('1', $kq->lyDoThatBai);
    }

    /** @test */
    public function thuc_te_NHIEU_hon_khai_bao_thi_khong_chan()
    {
        // Bat doi xung CO CHU DICH: metadata sai nhung du lieu du. Chan o day la chan nham
        // mot tep von day du.
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thanhCong('YT002', ['CT03']),
        ], 1, 2);

        $this->assertTrue($kq->thanhCong);
    }

    /** @test */
    public function hong_ngay_tu_dau_tep()
    {
        $kq = CtdtImportFileResult::thatBaiSom('Khong doc duoc noi dung XML cua goi');

        $this->assertFalse($kq->thanhCong);
        $this->assertSame('Khong doc duoc noi dung XML cua goi', $kq->lyDoThatBai);
        $this->assertSame([], $kq->ketQua);
        $this->assertSame(0, $kq->soThanhCong);
    }

    /** @test */
    public function hai_lop_dung_chung_ten_thuoc_tinh()
    {
        // Controller doc $kq->thanhCong va $kq->lyDoThatBai ma khong quan tam nhan duoc lop
        // nao. Doi ten mot ben se lam giao dien im lang bao sai.
        foreach ([CtdtImportResult::thatBai('x'), CtdtImportFileResult::thatBaiSom('x')] as $kq) {
            $this->assertObjectHasAttribute('thanhCong', $kq);
            $this->assertObjectHasAttribute('lyDoThatBai', $kq);
        }
    }
}
