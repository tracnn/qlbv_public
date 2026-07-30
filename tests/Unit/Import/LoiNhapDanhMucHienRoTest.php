<?php

namespace Tests\Unit\Import;

use Tests\TestCase;

/**
 * Khi nhap danh muc that bai, nguoi dung phai doc duoc LY DO.
 *
 * Da gap that: tep ICD 52.551 dong lam tien trinh chet vi vuot 128 MB / 120 giay. Fatal error
 * KHONG phai \Exception nen catch cu khong bat duoc, controller tra ve trang loi HTML thay vi
 * JSON, va Dropzone chi hien duoc chuoi du phong "Loi khi tai len" - khong ai biet chuyen gi
 * da xay ra, ke ca khi doc log.
 */
class LoiNhapDanhMucHienRoTest extends TestCase
{
    private function maController()
    {
        return file_get_contents(app_path('Http/Controllers/Category/CategoryBHYTController.php'));
    }

    /** @test */
    public function nhap_danh_muc_bat_ca_error_khong_chi_exception()
    {
        // \Error (TypeError, kiet bo nho bat duoc...) khong la con cua \Exception.
        $ma = $this->maController();
        $vt = strpos($ma, 'public function import(Request $request)');

        $this->assertNotFalse($vt, 'Khong tim thay ham import');

        $than = substr($ma, $vt, 3000);

        $this->assertContains('catch (\Throwable', $than,
            'Ham import con bat rieng \Exception nen loi \Error tra ve HTML, khong phai JSON');
    }

    /** @test */
    public function nhap_danh_muc_duoc_noi_gioi_han_bo_nho_va_thoi_gian()
    {
        // Doc tep ICD 52.551 dong ton ~102 MB va ~67 giay ngay sau khi da toi uu; gioi han
        // mac dinh cua may chu la 128 MB / 120 giay - khong con cho cho phan ghi.
        $ma = $this->maController();
        $vt = strpos($ma, 'public function import(Request $request)');
        $than = substr($ma, $vt, 3000);

        $this->assertContains("ini_set('memory_limit'", $than,
            'Ham import chua noi gioi han bo nho');
        $this->assertContains('set_time_limit(', $than,
            'Ham import chua noi gioi han thoi gian');
    }

    /** @test */
    public function dropzone_cho_du_lau_cho_tep_danh_muc_lon()
    {
        // Do that: nhap 52.551 dong ICD mat 265 giay. Dropzone dat 300 giay la qua sat - tep
        // lon hon mot chut la trinh duyet tu huy yeu cau giua luc may chu dang ghi, va nguoi
        // dung thay bao loi trong khi du lieu VAN dang vao.
        $js = file_get_contents(resource_path('views/category/bhyt/import.blade.php'));

        $this->assertRegExp('/timeout:\s*(\d+)/', $js, 'Khong tim thay cau hinh timeout');
        preg_match('/timeout:\s*(\d+)/', $js, $m);

        $this->assertGreaterThanOrEqual(1800000, (int) $m[1],
            'Dropzone phai cho it nhat bang set_time_limit(1800) o phia may chu');
    }

    /** @test */
    public function thong_bao_du_phong_kem_ma_http()
    {
        // Khong con JSON de doc thi it nhat phai biet la 500, 413 hay 504.
        $js = file_get_contents(resource_path('views/category/bhyt/import.blade.php'));

        $this->assertContains('xhr', $js,
            'Handler loi cua Dropzone chua dung xhr nen khong bao duoc ma HTTP');
        $this->assertContains('HTTP ', $js,
            'Thong bao du phong chua kem ma HTTP');
    }
}
