<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\DoiTuongKcbCatalog;
use Tests\TestCase;

class DoiTuongKcbCatalogTest extends TestCase
{
    private function danhMuc(): array
    {
        return [
            '1.1' => ['ten' => 'Dung noi dang ky ban dau', 'dung_dkbd' => true],
            '1.3' => ['ten' => 'Co phieu chuyen', 'can_noi_di' => true],
            '3.1' => ['ten' => 'Tu den chuyen sau', 'tu_den' => true, 'ngoai_tru_khong_huong' => true],
            '3.6' => ['ten' => 'Dan toc thieu so noi tru', 'tu_den' => true],
            '9'   => ['ten' => 'Khong KCB BHYT', 'khong_bhyt' => true],
        ];
    }

    /** @test */
    public function nhan_dien_ma_co_trong_danh_muc()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::coTrongDanhMuc('1.1', $dm));
        $this->assertTrue(DoiTuongKcbCatalog::coTrongDanhMuc('9', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('1.9', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('4', $dm));
    }

    /** @test */
    public function KHONG_duoc_khop_tien_to()
    {
        // Day la ca khoa bai hoc cua ca dot: ma dang phan cap co dau cham, khop tien to
        // lam '3' nuot ca 3.1/3.2/3.6 - dung khiem khuyet ma Task 2 di va.
        $dm = $this->danhMuc();
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('3', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('3.10', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('3', $dm));
    }

    /** @test */
    public function doc_thuoc_tinh_co_va_khong_co()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('1.3', $dm, 'can_noi_di', false));
        $this->assertFalse(DoiTuongKcbCatalog::thuocTinh('1.1', $dm, 'can_noi_di', false));
        $this->assertFalse(DoiTuongKcbCatalog::thuocTinh('KHONG-CO', $dm, 'can_noi_di', false));
        $this->assertSame('Khong KCB BHYT', DoiTuongKcbCatalog::thuocTinh('9', $dm, 'ten'));
        $this->assertNull(DoiTuongKcbCatalog::thuocTinh('9', $dm, 'khong_ton_tai'));
    }

    /** @test */
    public function la_tu_den()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('3.1', $dm));
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('3.6', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('1.3', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('9', $dm));
    }

    /** @test */
    public function chuan_hoa_chi_trim_khong_cat_gi()
    {
        $this->assertSame('3.1', DoiTuongKcbCatalog::chuanHoa('  3.1  '));
        $this->assertSame('1.17', DoiTuongKcbCatalog::chuanHoa('1.17'));
        $this->assertSame('', DoiTuongKcbCatalog::chuanHoa(null));
        $this->assertSame('', DoiTuongKcbCatalog::chuanHoa('   '));
    }

    /** @test */
    public function danh_muc_that_co_dung_27_ma()
    {
        // Van ban nguon nhay qua STT 22 nen chi co 27 ma, KHONG co ma '7.1'.
        $that = config('doi_tuong_kcb');
        $this->assertCount(27, $that);
        $this->assertArrayNotHasKey('7.1', $that);
        foreach (['1.1', '1.7', '1.11', '1.18', '2', '3.1', '3.6', '7', '7.2', '7.4', '8', '9', '10'] as $ma) {
            $this->assertArrayHasKey($ma, $that, "Thieu ma $ma");
        }
        foreach ($that as $ma => $muc) {
            $this->assertArrayHasKey('ten', $muc, "Muc '$ma' thieu khoa 'ten'");
        }
    }

    /** @test */
    public function danh_muc_that_khai_dung_cac_thuoc_tinh_quy_tac_dua_vao()
    {
        $that = config('doi_tuong_kcb');
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('1.3', $that, 'can_noi_di', false));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('9', $that, 'khong_bhyt', false));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('3.1', $that, 'ngoai_tru_khong_huong', false));
        $this->assertSame(100, DoiTuongKcbCatalog::thuocTinh('1.2', $that, 'muc_huong_co_dinh'));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('7.2', $that, 'linh_thuoc_khong_kham', false));
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('1.15', $that));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('1.5', $that));
    }
}
