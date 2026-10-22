<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\XuLyChetLuot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class XuLyChetLuotTest extends TestCase
{
    const KHOA = 'sinhxml3176:test:khoa-chet';

    protected function tearDown()
    {
        Cache::forget(self::KHOA);
        XuLyChetLuot::ketThuc();
        parent::tearDown();
    }

    public function test_fatal_thi_nha_khoa_va_log_critical_kem_id()
    {
        XuLyChetLuot::dangKy(self::KHOA);
        XuLyChetLuot::datDangNap([11, 22]);
        Cache::put(self::KHOA, true, 5);
        Log::shouldReceive('critical')->once()->with(\Mockery::on(function ($m) {
            return strpos($m, 'Allowed memory size') !== false && strpos($m, '11,22') !== false;
        }));

        $this->assertTrue(XuLyChetLuot::xuLy(['type' => E_ERROR, 'message' => 'Allowed memory size exhausted']));
        $this->assertFalse(Cache::has(self::KHOA));
    }

    public function test_khong_phai_fatal_hoac_khong_co_loi_thi_giu_khoa()
    {
        XuLyChetLuot::dangKy(self::KHOA);
        Cache::put(self::KHOA, true, 5);
        Log::shouldReceive('critical')->never();

        $this->assertFalse(XuLyChetLuot::xuLy(null));
        $this->assertFalse(XuLyChetLuot::xuLy(['type' => E_WARNING, 'message' => 'x']));
        $this->assertFalse(XuLyChetLuot::xuLy(['type' => E_NOTICE, 'message' => 'x']));
        $this->assertTrue(Cache::has(self::KHOA));
    }
}
