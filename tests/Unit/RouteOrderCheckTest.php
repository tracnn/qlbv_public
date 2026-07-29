<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

class RouteOrderCheckTest extends TestCase
{
    /**
     * Ten route => URI. Chot cung de chan viec vo tinh doi URL.
     *
     * Cac blade hardcode url('khth/order-check-ref-index') va
     * url('khth/order-check-rule-index'), khong chi dung route(). Doi URL la vo.
     */
    protected function banDo()
    {
        return [
            'khth.order-check-index' => 'khth/order-check-index',
            'khth.order-check-summary' => 'khth/order-check-index/summary',
            'khth.order-check-scan-stats' => 'khth/order-check-index/scan-stats',
            'khth.order-check-fetch' => 'khth/order-check-index/fetch',
            'khth.order-check-update-status' => 'khth/order-check-index/update-status',
            'khth.order-check-export' => 'khth/order-check-index/export',
            'khth.order-check-ref-index' => 'khth/order-check-ref-index',
            'khth.order-check-ref-fetch' => 'khth/order-check-ref-index/fetch',
            'khth.order-check-ref-store' => 'khth/order-check-ref-index',
            'khth.order-check-ref-update' => 'khth/order-check-ref-index/{id}',
            'khth.order-check-ref-destroy' => 'khth/order-check-ref-index/{id}',
            'khth.order-check-rule-index' => 'khth/order-check-rule-index',
            'khth.order-check-rule-fetch' => 'khth/order-check-rule-index/fetch',
            'khth.order-check-rule-update' => 'khth/order-check-rule-index/{id}',
            'khth.order-check-rule-toggle' => 'khth/order-check-rule-index/{id}/toggle',
        ];
    }

    /** @test */
    public function du_15_route_va_url_khong_doi()
    {
        foreach ($this->banDo() as $ten => $uri) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertSame($uri, $r->uri(), "Route $ten bi doi URL");
        }
    }

    /** @test */
    public function moi_route_deu_dung_quyen_order_check()
    {
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('checkrole:order-check', $mw,
                "Route $ten chua chuyen sang quyen order-check");
            $this->assertNotContains('checkrole:administrator', $mw,
                "Route $ten van con quyen administrator");
        }
    }

    /** @test */
    public function van_nam_trong_nhom_xac_thuc()
    {
        // Nhom ngoai cung cua web.php la ['auth', 'check.first.login']. Neu chen nhom moi
        // ra ngoai nham thi route thanh cong khai - loi bao mat im lang.
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }
}
