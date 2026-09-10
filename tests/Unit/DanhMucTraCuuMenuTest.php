<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Luoi an toan cho BAY THU TU NAP CONFIG.
 *
 * Laravel nap cac tep config theo thu tu chu cai, 'adminlte' chay TRUOC
 * 'danh_muc_tra_cuu'. Neu ai do doi 'require' thanh config('danh_muc_tra_cuu') trong
 * config/adminlte.php thi khoa do chua ton tai, tra ve null, va submenu thanh RONG -
 * khong loi, khong canh bao. Test nay do len khi dieu do xay ra.
 */
class DanhMucTraCuuMenuTest extends TestCase
{
    /** Tim de quy mot muc menu theo 'text'. */
    private function timMuc(array $menu, $text)
    {
        foreach ($menu as $muc) {
            if (is_array($muc) && isset($muc['text']) && $muc['text'] === $text) {
                return $muc;
            }

            if (is_array($muc) && !empty($muc['submenu'])) {
                $tim = $this->timMuc($muc['submenu'], $text);
                if ($tim !== null) {
                    return $tim;
                }
            }
        }

        return null;
    }

    /** @test */
    public function co_submenu_danh_muc_tra_cuu()
    {
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $this->assertNotNull($muc, 'Khong tim thay submenu "Danh muc tra cuu" trong adminlte.menu');
        $this->assertArrayHasKey('submenu', $muc);
    }

    /** @test */
    public function submenu_sinh_du_tu_so_dang_ky()
    {
        $so = config('danh_muc_tra_cuu');
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $this->assertCount(count($so), $muc['submenu'],
            'So muc submenu khong khop so dang ky - kiem tra lai cach doc so trong adminlte.php');
    }

    /** @test */
    public function moi_muc_submenu_tro_dung_slug()
    {
        $so = config('danh_muc_tra_cuu');
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $url = array_column($muc['submenu'], 'url');

        foreach ($so as $khoa => $dm) {
            $this->assertContains('danh-muc-tra-cuu/' . $khoa, $url,
                "Thieu muc menu cho danh muc '$khoa'");
        }
    }
}
