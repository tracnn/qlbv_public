<?php

namespace Tests\Unit\Mcct;

use Route;
use Tests\TestCase;

class RouteMcctTest extends TestCase
{
    /** Ten route => URI. Chot cung de chan viec vo tinh doi URL. */
    protected function banDo()
    {
        return [
            'insurance.mcct' => 'insurance/mcct',
            'insurance.mcct.search' => 'insurance/mcct/search',
            'insurance.mcct.api' => 'insurance/mcct/api',
        ];
    }

    /**
     * Man web LUON goi cong tu 15/9/2026: endpoint doc ket qua da luu da bi go. Chan viec vo
     * tinh them lai - no se dua man hinh ve hien so lieu cu ma nguoi dung khong hay biet.
     */
    /** @test */
    public function khong_con_route_doc_ket_qua_da_luu()
    {
        $this->assertNull(Route::getRoutes()->getByName('insurance.mcct.gan-nhat'));
    }

    /** @test */
    public function du_ba_route_va_url_khong_doi()
    {
        foreach ($this->banDo() as $ten => $uri) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertSame($uri, $r->uri(), "Route $ten bi doi URL");
        }
    }

    /**
     * Nhom ngoai cung cua web.php la ['auth']. Chen nham ra ngoai nhom do thi route thanh
     * cong khai - loi bao mat im lang.
     */
    /** @test */
    public function van_nam_trong_nhom_xac_thuc()
    {
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }

    /**
     * Cung muc quyen voi man tra cuu the BHYT ngay canh no. Siet rieng MCCT trong khi man
     * tra cuu the van mo la mot su bat nhat kho giai thich.
     */
    /** @test */
    public function cung_muc_quyen_voi_man_tra_cuu_the()
    {
        $cuaThe = Route::getRoutes()->getByName('insurance.check-card')->gatherMiddleware();

        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertSame(array_values($cuaThe), array_values($mw),
                "Route $ten khong cung muc quyen voi man tra cuu the");
        }
    }
}
