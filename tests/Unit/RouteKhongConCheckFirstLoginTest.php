<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

/**
 * Canh gac: middleware CheckFirstLogin da bi xoa vi no gan superadministrator cho
 * bat ky ai dang nhap dau tien. Neu ai do khoi phuc lai, test nay do.
 */
class RouteKhongConCheckFirstLoginTest extends TestCase
{
    /** @test */
    public function khong_route_nao_con_middleware_check_first_login()
    {
        $pham = [];

        foreach (Route::getRoutes() as $route) {
            if (in_array('check.first.login', $route->middleware())) {
                $pham[] = $route->uri();
            }
        }

        $this->assertSame([], $pham,
            'Con route dung check.first.login: ' . implode(', ', $pham));
    }

    /** @test */
    public function lop_middleware_khong_con_ton_tai()
    {
        $this->assertFalse(class_exists(\App\Http\Middleware\CheckFirstLogin::class),
            'CheckFirstLogin da duoc khoi phuc - xem docs/superpowers/specs/2026-08-01-khoi-tao-superadmin-design.md');
    }

    /**
     * Man khoi tao phai con song sau khi go middleware, va van yeu cau dang nhap.
     *
     * @test
     */
    public function man_khoi_tao_van_nam_trong_nhom_xac_thuc()
    {
        $route = Route::getRoutes()->getByName('setup.quan-tri-dau-tien');

        $this->assertNotNull($route, 'Mat route man khoi tao');
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
