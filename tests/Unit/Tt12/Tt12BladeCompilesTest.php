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
        );

        foreach ($ten as $mot) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($mot),
                'Thieu route ' . $mot
            );
        }
    }
}
