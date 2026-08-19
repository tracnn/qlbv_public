<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Canh route va quyen cua module chung tu dien tu.
 *
 * VI SAO DOC QUA Route facade chu khong quet chuoi trong routes/web.php: quet chuoi se xanh
 * ca khi route nam ngoai group quyen, vi chuoi 'checkrole:xml-man' van xuat hien o cho khac
 * trong tep. Doc middleware da gom cua tung route la cach duy nhat noi dung ve quyen.
 */
class CtdtRouteTest extends TestCase
{
    public function cacRoute()
    {
        return [
            'bhyt.ctdt.index',
            'bhyt.ctdt.fetch-data',
            'bhyt.ctdt.import.index',
            'bhyt.ctdt.upload',
            'bhyt.ctdt.detail',
            'bhyt.ctdt.detail.tab',
            'bhyt.ctdt.delete',
        ];
    }

    /** @test */
    public function bay_route_deu_duoc_dang_ky()
    {
        foreach ($this->cacRoute() as $ten) {
            $this->assertNotNull(Route::getRoutes()->getByName($ten), 'Thieu route ' . $ten);
        }
    }

    /** @test */
    public function moi_route_deu_yeu_cau_quyen_xml_man()
    {
        // Quen mot route ngoai group quyen nghia la bat ky ai dang nhap cung xem duoc ho so
        // benh nhan - va khong co gi bao dong.
        foreach ($this->cacRoute() as $ten) {
            $middleware = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('checkrole:xml-man', $middleware, $ten . ' thieu quyen xml-man');
        }
    }

    /** @test */
    public function xoa_ho_so_chi_danh_cho_superadministrator()
    {
        // Xoa mot ho so da co MaGD la xoa dau vet doi soat voi BHXH.
        $middleware = Route::getRoutes()->getByName('bhyt.ctdt.delete')->gatherMiddleware();

        $this->assertContains('checkrole:superadministrator', $middleware);
    }

    /** @test */
    public function route_tro_dung_controller()
    {
        $mongDoi = 'App\Http\Controllers\BHYT\BHYTCtdtController';

        foreach ($this->cacRoute() as $ten) {
            $action = Route::getRoutes()->getByName($ten)->getActionName();

            $this->assertStringStartsWith($mongDoi . '@', $action, $ten . ' tro sai controller');
        }
    }

    /** @test */
    public function route_xoa_dung_phuong_thuc_DELETE()
    {
        $this->assertContains('DELETE', Route::getRoutes()->getByName('bhyt.ctdt.delete')->methods());
    }

    /** @test */
    public function route_tai_len_dung_phuong_thuc_POST()
    {
        $this->assertContains('POST', Route::getRoutes()->getByName('bhyt.ctdt.upload')->methods());
    }

    /** @test */
    public function menu_khai_dung_quyen_va_dung_route()
    {
        // Menu di qua AppServiceProvider::filterMenu, ham do CHI kiem hasRole(). Khai thieu
        // checkrole thi moi nguoi dang nhap deu thay muc nay trong menu roi bam vao bi 403.
        $menu = config('adminlte.menu');

        $muc = $this->timMuc($menu, 'Chứng từ điện tử');

        $this->assertNotNull($muc, 'Thieu muc menu "Chung tu dien tu" trong config/adminlte.php');
        $this->assertSame('xml-man', $muc['checkrole']);

        $tenRoute = [];

        foreach ($muc['submenu'] as $con) {
            $tenRoute[] = $con['route'];
        }

        $this->assertContains('bhyt.ctdt.index', $tenRoute);
        $this->assertContains('bhyt.ctdt.import.index', $tenRoute);
    }

    private function timMuc($items, $nhan)
    {
        foreach ((array) $items as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === $nhan) {
                return $item;
            }

            if (is_array($item) && isset($item['submenu'])) {
                $tim = $this->timMuc($item['submenu'], $nhan);

                if ($tim !== null) {
                    return $tim;
                }
            }
        }

        return null;
    }
}
