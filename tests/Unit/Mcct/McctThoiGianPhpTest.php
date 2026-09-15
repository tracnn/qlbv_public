<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\McctTraCuuChung;
use Tests\TestCase;

/**
 * Tran thoi gian PHP cho mot lan tra cuu MCCT.
 *
 * VI SAO CAN: may chu chinh thuc dat max_execution_time = 120 giay. Cong BHXH duoc phep
 * cham toi timeout_tong, va luong 401 goi cong HAI lan - neu PHP ngat giua chung thi nguoi
 * dung nhan mot trang loi 500 khong noi gi, trong khi luot goi cong da tieu.
 */
class McctThoiGianPhpTest extends TestCase
{
    /** @test */
    public function du_cho_hai_lan_goi_cong_cham_nhat()
    {
        config(['mcct.timeout_ket_noi' => 15, 'mcct.timeout_tong' => 120]);

        $gioiHan = McctTraCuuChung::gioiHanThoiGianPhp();

        // Luong 401: logout -> dang nhap lai -> goi lan hai. Moi lan goi cham nhat
        // timeout_ket_noi + timeout_tong.
        $this->assertGreaterThan(2 * (15 + 120), $gioiHan);
    }

    /** @test */
    public function vuot_tran_120_giay_mac_dinh_cua_may_chu()
    {
        config(['mcct.timeout_ket_noi' => 15, 'mcct.timeout_tong' => 120]);

        $this->assertGreaterThan(120, McctTraCuuChung::gioiHanThoiGianPhp());
    }

    /** @test */
    public function bam_theo_cau_hinh_chu_khong_chot_cung()
    {
        config(['mcct.timeout_ket_noi' => 10, 'mcct.timeout_tong' => 30]);
        $nho = McctTraCuuChung::gioiHanThoiGianPhp();

        config(['mcct.timeout_ket_noi' => 10, 'mcct.timeout_tong' => 200]);
        $lon = McctTraCuuChung::gioiHanThoiGianPhp();

        $this->assertGreaterThan($nho, $lon);
    }

    /** Timeout cong 120 giay theo yeu cau 15/9/2026. */
    /** @test */
    public function cau_hinh_timeout_tong_la_120_giay()
    {
        $cauHinh = require base_path('config/mcct.php');

        $this->assertSame(120, $cauHinh['timeout_tong']);
    }
}
