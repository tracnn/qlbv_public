<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

/**
 * Chot bon route cua man tra cuu loi ho so, cung khuon voi RouteOrderCheckTest: neu nhom
 * route moi vo tinh dat NGOAI nhom xac thuc ngoai cung ['auth'] thi route thanh cong khai
 * - loi bao mat im lang, khong bao gio thay bang mat thuong khi doc web.php.
 */
class RouteTraCuuLoiHoSoTest extends TestCase
{
    protected function banDo()
    {
        return [
            'khth.tra-cuu-loi-ho-so' => 'khth/tra-cuu-loi-ho-so',
            'khth.tra-cuu-loi-ho-so-tra-cuu' => 'khth/tra-cuu-loi-ho-so/tra-cuu',
            'khth.tra-cuu-loi-ho-so-in' => 'khth/tra-cuu-loi-ho-so/in',
            'khth.tra-cuu-loi-ho-so-tra-lai-the' => 'khth/tra-cuu-loi-ho-so/tra-lai-the',
        ];
    }

    /** @test */
    public function du_4_route_va_url_khong_doi()
    {
        foreach ($this->banDo() as $ten => $uri) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertSame($uri, $r->uri(), "Route $ten bi doi URL");
        }
    }

    /** @test */
    public function ca_bon_route_deu_dung_quyen_tra_cuu_loi_ho_so()
    {
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('checkrole:tra-cuu-loi-ho-so', $mw,
                "Route $ten chua gan quyen tra-cuu-loi-ho-so");
        }
    }

    /** @test */
    public function van_nam_trong_nhom_xac_thuc()
    {
        // Nhom ngoai cung cua web.php la ['auth']. Neu nhom moi bi chen ra ngoai nham thi
        // route thanh cong khai - dung mau test da co san cua RouteOrderCheckTest.
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }

    /** @test */
    public function tra_lai_the_co_throttle()
    {
        $mw = Route::getRoutes()->getByName('khth.tra-cuu-loi-ho-so-tra-lai-the')->gatherMiddleware();

        $coThrottle = false;
        foreach ($mw as $m) {
            if (strpos($m, 'throttle:') === 0) {
                $coThrottle = true;
            }
        }

        $this->assertTrue($coThrottle, 'Route tra-lai-the phai co throttle de chan spam dispatch job');
    }
}
