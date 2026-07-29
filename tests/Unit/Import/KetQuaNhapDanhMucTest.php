<?php

namespace Tests\Unit\Import;

use Tests\TestCase;
use App\Services\Import\KetQuaNhapDanhMuc;

class KetQuaNhapDanhMucTest extends TestCase
{
    /** @test */
    public function moi_tao_thi_moi_so_deu_bang_khong()
    {
        $a = (new KetQuaNhapDanhMuc())->toArray();

        foreach (['so_da_nhap', 'so_da_cap_nhat', 'so_khong_doi', 'so_bo_qua', 'so_loi'] as $x) {
            $this->assertSame(0, $a[$x], $x);
        }

        $this->assertSame([], $a['dong_loi']);
    }

    /** @test */
    public function dem_dung_tung_loai()
    {
        $k = new KetQuaNhapDanhMuc();
        $k->themNhap();
        $k->themNhap();
        $k->themCapNhat();
        $k->themKhongDoi();
        $k->themBoQua(5, 'Thieu MA_THUOC');
        $k->themLoi(9, 'Loi ghi');

        $a = $k->toArray();

        $this->assertSame(2, $a['so_da_nhap']);
        $this->assertSame(1, $a['so_da_cap_nhat']);
        $this->assertSame(1, $a['so_khong_doi']);
        $this->assertSame(1, $a['so_bo_qua']);
        $this->assertSame(1, $a['so_loi']);
    }

    /** @test */
    public function ghi_so_dong_excel_de_nguoi_dung_mo_tep_sua_duoc()
    {
        $k = new KetQuaNhapDanhMuc();
        $k->themLoi(42, 'Trung khoa');

        $this->assertSame([['dong' => 42, 'ly_do' => 'Trung khoa']], $k->toArray()['dong_loi']);
    }

    /** @test */
    public function cat_danh_sach_dong_loi_nhung_van_dem_du()
    {
        $k = new KetQuaNhapDanhMuc();

        for ($i = 1; $i <= 50; $i++) {
            $k->themLoi($i, 'Loi ' . $i);
        }

        $a = $k->toArray();

        $this->assertSame(50, $a['so_loi'], 'Phai dem du');
        $this->assertCount(KetQuaNhapDanhMuc::TOI_DA_DONG_LOI, $a['dong_loi']);
    }

    /** @test */
    public function tom_tat_co_du_nam_con_so()
    {
        $k = new KetQuaNhapDanhMuc();
        $k->themNhap();

        $t = $k->tomTat();

        foreach (['thêm', 'cập nhật', 'không đổi', 'bỏ qua', 'lỗi'] as $tu) {
            $this->assertContains($tu, $t, $tu);
        }
    }

    /** @test */
    public function co_ghi_nhan_gi_khong()
    {
        $this->assertFalse((new KetQuaNhapDanhMuc())->coGhi());

        $chen = new KetQuaNhapDanhMuc();
        $chen->themNhap();
        $this->assertTrue($chen->coGhi());

        $capNhat = new KetQuaNhapDanhMuc();
        $capNhat->themCapNhat();
        $this->assertTrue($capNhat->coGhi());

        // Chi bo qua / khong doi thi KHONG coi la da ghi.
        $khong = new KetQuaNhapDanhMuc();
        $khong->themKhongDoi();
        $khong->themBoQua(2, 'x');
        $this->assertFalse($khong->coGhi());
    }
}
