<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\NguongMienCungChiTra;
use Tests\TestCase;

class NguongMienCungChiTraTest extends TestCase
{
    protected function bangLuong()
    {
        return [
            '2023-07-01' => 1800000,
            '2024-07-01' => 2340000,
        ];
    }

    /** @test */
    public function lay_dung_muc_luong_theo_moc_hieu_luc()
    {
        $this->assertSame(1800000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-01-15', $this->bangLuong()));
        $this->assertSame(2340000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2026-09-03', $this->bangLuong()));
    }

    /**
     * Dung NGAY doi luong: moc la '2024-07-01' nen chinh ngay do da ap muc moi.
     * Lech mot ngay o day nghia la tinh sai nguong cho ca mot ngay lam viec.
     */
    /** @test */
    public function dung_ngay_doi_luong_thi_ap_muc_moi()
    {
        $this->assertSame(2340000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-07-01', $this->bangLuong()));
        $this->assertSame(1800000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-06-30', $this->bangLuong()));
    }

    /**
     * Ngay truoc moi moc: tra 0 chu KHONG roi ve muc dau tien. Roi ve nghia la bia ra mot
     * nguong cho khoang thoi gian chua khai - sai im lang.
     */
    /** @test */
    public function ngay_truoc_moi_moc_thi_tra_khong()
    {
        $this->assertSame(0,
            NguongMienCungChiTra::luongCoSoTaiNgay('2020-01-01', $this->bangLuong()));
    }

    /** @test */
    public function nguong_bang_sau_thang_luong_co_so()
    {
        $this->assertSame(14040000.0,
            NguongMienCungChiTra::nguong('2026-09-03', $this->bangLuong(), 6));
    }

    /** @test */
    public function tren_nguong_thi_du_dieu_kien()
    {
        $this->assertTrue(NguongMienCungChiTra::duDieuKien(14040001, 14040000));
    }

    /**
     * NĐ 188/2025 dung cau chu "LON HON 6 thang luong co so": bang dung nguong la CHUA du.
     * Khac biet nay chi lo ra o dung mot truong hop, va luc lo ra thi da tra loi sai nguoi
     * benh roi.
     */
    /** @test */
    public function bang_dung_nguong_thi_chua_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(14040000, 14040000));
    }

    /** @test */
    public function duoi_nguong_thi_chua_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(12500000, 14040000));
    }

    /**
     * Bang luong rong (cau hinh thieu) thi nguong bang 0, va khi do KHONG duoc ket luan la
     * ai cung du dieu kien. duDieuKien() nhan nguong 0 phai tra false.
     */
    /** @test */
    public function nguong_khong_thi_khong_ket_luan_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(12500000, 0));
    }
}
