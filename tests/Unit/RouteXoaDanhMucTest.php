<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

class RouteXoaDanhMucTest extends TestCase
{
    /** @test */
    public function hai_route_xoa_chi_danh_cho_superadministrator()
    {
        foreach (['category-bhyt.xoa-danh-muc-dem', 'category-bhyt.xoa-danh-muc'] as $ten) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");

            $mw = $r->gatherMiddleware();

            $this->assertContains('checkrole:superadministrator', $mw,
                "Route $ten phai gioi han cho superadministrator");
            $this->assertNotContains('checkrole:category-manager', $mw,
                "Route $ten khong duoc mo cho ca category-manager");
            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }

    /** @test */
    public function route_chi_tiet_dung_quyen_category_manager()
    {
        $r = Route::getRoutes()->getByName('category-bhyt.chi-tiet');

        $this->assertNotNull($r, 'Thieu route category-bhyt.chi-tiet');

        $mw = $r->gatherMiddleware();

        $this->assertContains('checkrole:category-manager', $mw);
        $this->assertContains('auth', $mw);
    }
}
