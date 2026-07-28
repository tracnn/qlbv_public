<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\MaBenh;

class MaBenhTest extends TestCase
{
    /** @test */
    public function tach_chuoi_ma_don()
    {
        $this->assertSame(['A00'], MaBenh::tach('A00'));
        $this->assertSame(['A00'], MaBenh::tach('  A00  '));
    }

    /** @test */
    public function bo_phan_tu_rong_khi_co_dau_cham_phay_dan_dau()
    {
        // Du lieu that: icd_sub_code luon co dang ';A04.9;E87.8'. Khong bo phan tu rong
        // thi MOI phieu co chan doan phu deu thanh vi pham - 39.242 phieu moi 7 ngay.
        $this->assertSame(['A04.9'], MaBenh::tach(';A04.9'));
        $this->assertSame(['A04.9', 'E87.8'], MaBenh::tach(';A04.9;E87.8'));
        $this->assertSame(['A04.9', 'J44.8', 'N17.9'], MaBenh::tach(';A04.9;J44.8;N17.9'));
    }

    /** @test */
    public function chuoi_rong_hoac_toan_dau_phan_cach_tra_mang_rong()
    {
        $this->assertSame([], MaBenh::tach(''));
        $this->assertSame([], MaBenh::tach(null));
        $this->assertSame([], MaBenh::tach(';'));
        $this->assertSame([], MaBenh::tach(';;;'));
        $this->assertSame([], MaBenh::tach('  ;  ;  '));
    }

    /** @test */
    public function bo_ma_trung_trong_cung_mot_chuoi()
    {
        $this->assertSame(['A00'], MaBenh::tach(';A00;A00'));
    }

    /** @test */
    public function gom_danh_dau_vi_tri_chinh_phu()
    {
        $this->assertSame(['A00' => 'chinh'], MaBenh::gom('A00', ''));
        $this->assertSame(['B00' => 'phu'], MaBenh::gom('', ';B00'));
        $this->assertSame(['A00' => 'chinh', 'B00' => 'phu'], MaBenh::gom('A00', ';B00'));
    }

    /** @test */
    public function ma_xuat_hien_o_ca_hai_cho_chi_ke_mot_lan()
    {
        // Cung mot ma khai sai, bao hai lan khong giup nguoi sua.
        $this->assertSame(['A00' => 'ca_hai'], MaBenh::gom('A00', ';A00'));
        $this->assertSame(['A00' => 'ca_hai', 'B00' => 'phu'], MaBenh::gom('A00', ';A00;B00'));
    }

    /** @test */
    public function gom_hai_chuoi_rong_tra_mang_rong()
    {
        $this->assertSame([], MaBenh::gom('', ''));
        $this->assertSame([], MaBenh::gom(null, null));
    }

    /** @test */
    public function nhan_vi_tri_doc_duoc()
    {
        $this->assertSame('chẩn đoán chính', MaBenh::nhanViTri('chinh'));
        $this->assertSame('chẩn đoán phụ', MaBenh::nhanViTri('phu'));
        $this->assertSame('chẩn đoán chính và phụ', MaBenh::nhanViTri('ca_hai'));
    }
}
