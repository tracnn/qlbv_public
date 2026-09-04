<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\ServiceOverlapChecker;
use Tests\TestCase;

class ServiceOverlapCheckerTest extends TestCase
{
    /** @test */
    public function chong_nhau_khi_hai_khoang_giao_nhau()
    {
        // A 08:00-09:00, B 08:30-09:30 -> chồng
        $this->assertTrue(ServiceOverlapChecker::chongNhau(
            '202608150800', '202608150900', '202608150830', '202608150930'
        ));
    }

    /** @test */
    public function chong_nhau_khi_mot_khoang_nam_trong_khoang_kia()
    {
        // A 08:00-10:00 chứa B 08:30-09:00
        $this->assertTrue(ServiceOverlapChecker::chongNhau(
            '202608150800', '202608151000', '202608150830', '202608150900'
        ));
    }

    /** @test */
    public function khong_chong_khi_noi_tiep_nhau()
    {
        // A kết thúc đúng lúc B bắt đầu -> KHÔNG chồng (biên nửa mở)
        $this->assertFalse(ServiceOverlapChecker::chongNhau(
            '202608150800', '202608150900', '202608150900', '202608151000'
        ));
    }

    /** @test */
    public function khong_chong_khi_tach_roi()
    {
        $this->assertFalse(ServiceOverlapChecker::chongNhau(
            '202608150800', '202608150830', '202608150900', '202608151000'
        ));
    }

    /** @test */
    public function guard_khi_thieu_hoac_sai_dinh_dang()
    {
        $this->assertFalse(ServiceOverlapChecker::chongNhau('', '202608150900', '202608150830', '202608150930'));
        $this->assertFalse(ServiceOverlapChecker::chongNhau('202608150800', null, '202608150830', '202608150930'));
        $this->assertFalse(ServiceOverlapChecker::chongNhau('abc', '202608150900', '202608150830', '202608150930'));
    }

    /** @test */
    public function guard_khi_khoang_nguoc_dau_cuoi()
    {
        // ngày kết quả trước ngày thực hiện -> dữ liệu hỏng, không kết luận
        $this->assertFalse(ServiceOverlapChecker::chongNhau(
            '202608150900', '202608150800', '202608150830', '202608150930'
        ));
    }
}
