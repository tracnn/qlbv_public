<?php

namespace Tests\Unit;

use App\Services\Category\NhanTruong;
use Tests\TestCase;

class NhanTruongTest extends TestCase
{
    /** @test */
    public function truong_co_trong_mapping_thi_lay_ten_chuan()
    {
        // catalog_import_mapping: 'ma_thuoc' => ['MA_THUOC', 'Mã thuốc', 'MA THUOC']
        // Phan tu DAU TIEN la ten chuan.
        $this->assertSame('MA_THUOC', NhanTruong::cua('medicine', 'ma_thuoc'));
        $this->assertSame('MA_NGHE_NGHIEP', NhanTruong::cua('job_categories', 'job_code'));
    }

    /** @test */
    public function truong_ngoai_mapping_thi_giu_ten_cot_tho()
    {
        // id, created_at khong nam trong mapping nhap khau.
        // Luu y: ma_cskcb THUC TE co trong mapping cua 'medicine'
        // (dung khi nhap khau de xac dinh co so), nen khong dung lam vi du o day —
        // khac gia dinh trong brief goc.
        $this->assertSame('created_at', NhanTruong::cua('medicine', 'created_at'));
        $this->assertSame('id', NhanTruong::cua('medicine', 'id'));
    }

    /** @test */
    public function loai_khong_ton_tai_thi_giu_ten_cot_tho()
    {
        $this->assertSame('bat_ky', NhanTruong::cua('khong_co_loai_nay', 'bat_ky'));
    }
}
