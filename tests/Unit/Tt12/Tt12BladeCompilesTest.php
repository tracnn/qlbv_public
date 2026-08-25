<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

/**
 * Blade sai cu phap chi vo khi nguoi dung MO TRANG, khong phai luc build. Bien dich san
 * o day de loi lo ra trong bo test.
 */
class Tt12BladeCompilesTest extends TestCase
{
    public function cacBlade()
    {
        return array(
            array('import'),
            array('index'),
            array('detail'),
            array('tab-dong'),
            array('tab-loi'),
            array('tab-xml'),
        );
    }

    /**
     * @test
     * @dataProvider cacBlade
     */
    public function blade_bien_dich_duoc($ten)
    {
        $duongDan = resource_path('views/bhyt/tt12/' . $ten . '.blade.php');

        $this->assertFileExists($duongDan);

        $php = app('blade.compiler')->compileString(file_get_contents($duongDan));

        $tam = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($tam, $php);

        exec('php -l ' . escapeshellarg($tam), $ra, $ma);
        unlink($tam);

        $this->assertSame(0, $ma, $ten . ': ' . implode(PHP_EOL, $ra));
    }

    /** @test */
    public function moi_route_tt12_deu_duoc_dang_ky()
    {
        $ten = array(
            'bhyt.tt12.import.index', 'bhyt.tt12.upload', 'bhyt.tt12.index',
            'bhyt.tt12.fetch-data', 'bhyt.tt12.detail', 'bhyt.tt12.detail.tab',
            'bhyt.tt12.ky-va-gui', 'bhyt.tt12.ky-va-gui-nhieu',
            'bhyt.tt12.xuat.danh-sach', 'bhyt.tt12.xuat.loi', 'bhyt.tt12.delete',
            'bhyt.tt12.dong-bo-lai', 'bhyt.tt12.kiem-lai',
        );

        foreach ($ten as $mot) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($mot),
                'Thieu route ' . $mot
            );
        }
    }

    /** @test */
    public function man_chi_tiet_in_ca_ba_loai_loi_ke_ca_loi_nap()
    {
        // import_error tung duoc GHI ma khong man hinh nao in ra. Ba cot loi phai duoc doi
        // xu nhu nhau, khong thi mot cai lai am tham.
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/detail.blade.php'));

        foreach (array('import_error', 'signed_error', 'submit_error') as $cot) {
            $this->assertContains('$hoSo->' . $cot, $noiDung, 'Man chi tiet khong in ' . $cot);
        }
    }

    /** @test */
    public function man_danh_sach_co_cot_loi_nap()
    {
        $noiDung = file_get_contents(resource_path('views/bhyt/tt12/index.blade.php'));

        $this->assertContains('co_loi_nap', $noiDung);
    }

    /** @test */
    public function hai_route_cuu_ho_deu_nam_trong_chot_quyen_xml_man()
    {
        // dong-bo-lai GHI DE bang danh muc - chinh bang Xml3176Xml3Checker doc de giam
        // dinh. Nut bi an tren blade KHONG phai la chot quyen: ai cung goi thang POST
        // duoc. Khoi route 'bhyt/' da boc san checkrole:xml-man o tang nhom; test nay chot
        // rang hai route moi that su nam trong khoi do, khong bi ai keo ra ngoai.
        foreach (array('bhyt.tt12.dong-bo-lai', 'bhyt.tt12.kiem-lai') as $ten) {
            $route = app('router')->getRoutes()->getByName($ten);

            $this->assertNotNull($route, 'Thieu route ' . $ten);
            $this->assertContains(
                'checkrole:xml-man',
                $route->gatherMiddleware(),
                $ten . ' phai nam trong chot quyen xml-man'
            );
        }
    }

    /** @test */
    public function route_xoa_phai_co_chot_quyen_superadministrator()
    {
        // Xoa la thao tac khong hoan tac duoc va cham vao ho so cua NGUOI KHAC. Khuon da
        // co san ngay tren cung tep: bhyt.ctdt.delete mang dung middleware nay.
        $route = app('router')->getRoutes()->getByName('bhyt.tt12.delete');

        $this->assertNotNull($route);
        $this->assertContains(
            'checkrole:superadministrator',
            $route->gatherMiddleware(),
            'Route xoa TT12 phai co chot quyen giong bhyt.ctdt.delete'
        );
    }
}
