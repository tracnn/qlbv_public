<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

class MenuTt12Test extends TestCase
{
    const TEN_MUC = 'Danh mục TT12';

    /**
     * Tim khoi menu 'Danh muc TT12' bang de quy tren toan cay menu - khoi nay
     * la muc CAP 2, nam trong submenu cua cap 1 'Ho so XML' (cung nhom voi Xml3176,
     * Chung tu dien tu, Xml 4750...), khong phai muc cap 1.
     */
    protected function khoiTt12($cay = null)
    {
        $cay = $cay === null ? config('adminlte.menu') : $cay;

        foreach ($cay as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['text']) && $item['text'] === self::TEN_MUC) {
                return $item;
            }

            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $found = $this->khoiTt12($item['submenu']);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /** @test */
    public function khoi_menu_ton_tai_va_dung_quyen_xml_man()
    {
        $khoi = $this->khoiTt12();

        $this->assertNotNull($khoi, 'Khong tim thay khoi menu "' . self::TEN_MUC . '"');
        $this->assertSame('xml-man', $khoi['checkrole']);
    }

    /** @test */
    public function submenu_co_dung_hai_muc_tro_toi_route_co_that()
    {
        $khoi = $this->khoiTt12();

        $this->assertNotNull($khoi);
        $this->assertArrayHasKey('submenu', $khoi);
        $this->assertCount(2, $khoi['submenu']);

        $mong = [
            'Danh sách hồ sơ' => 'bhyt.tt12.index',
            'Nạp danh mục' => 'bhyt.tt12.import.index',
        ];

        $router = app('router');

        foreach ($khoi['submenu'] as $muc) {
            $this->assertArrayHasKey($muc['text'], $mong, 'Muc menu khong mong doi: ' . $muc['text']);
            $this->assertSame($mong[$muc['text']], $muc['route']);

            // Route phai ton tai that: mot muc menu tro toi route khong ton tai se nem
            // RouteNotFoundException ngay khi mo bat ky trang nao, vi sidebar dung tren
            // moi trang - day la kieu hong lam sap ca ung dung chu khong rieng man TT12.
            $route = $router->getRoutes()->getByName($muc['route']);
            $this->assertNotNull($route, 'Route "' . $muc['route'] . '" khong ton tai');
        }
    }

    /** @test */
    public function active_cua_nap_danh_muc_khong_lam_sang_danh_sach_ho_so()
    {
        $khoi = $this->khoiTt12();

        $danhSach = null;
        $napDanhMuc = null;

        foreach ($khoi['submenu'] as $muc) {
            if ($muc['text'] === 'Danh sách hồ sơ') {
                $danhSach = $muc;
            } elseif ($muc['text'] === 'Nạp danh mục') {
                $napDanhMuc = $muc;
            }
        }

        $this->assertNotNull($danhSach);
        $this->assertNotNull($napDanhMuc);

        // tt12/import khong duoc khop bat ky mau active nao cua "Danh sach ho so"
        foreach ($danhSach['active'] as $mau) {
            $this->assertStringStartsNotWith(
                'bhyt/tt12/import',
                $mau,
                'Mau active "' . $mau . '" cua "Danh sach ho so" se sang nham khi mo tt12/import'
            );
        }
    }

    /** @test */
    public function checkrole_cua_menu_khop_middleware_that_cua_route_index()
    {
        $khoi = $this->khoiTt12();

        $route = app('router')->getRoutes()->getByName('bhyt.tt12.index');
        $this->assertNotNull($route, 'Route bhyt.tt12.index khong ton tai');

        $middleware = $route->gatherMiddleware();

        $khopCheckrole = false;
        foreach ($middleware as $mw) {
            if ($mw === 'checkrole:' . $khoi['checkrole']) {
                $khopCheckrole = true;
                break;
            }
        }

        $this->assertTrue(
            $khopCheckrole,
            'checkrole "' . $khoi['checkrole'] . '" cua menu khong khop middleware that cua route bhyt.tt12.index: '
                . implode(', ', $middleware)
        );
    }
}
