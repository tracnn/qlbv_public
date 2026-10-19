<?php

namespace Tests\Unit\GoXmlCu;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Chot sau khi go module XML4210: khong con file nao tham chieu class, route, lenh cua no.
 */
class KhongConXmlCuTest extends TestCase
{
    use GoXmlCuGuard;

    /** @test */
    public function khong_con_tham_chieu_4210()
    {
        $viPham = $this->quetViPham(self::$KY_HIEU_4210);

        $this->assertSame([], $viPham, "Con tham chieu XML4210:\n" . implode("\n", $viPham));
    }

    /** @test */
    public function route_4210_da_go()
    {
        $this->assertFalse(Route::has('bhyt.index'));
        $this->assertFalse(Route::has('system.upload-xml'));
        $this->assertTrue(Route::has('bhyt.xml3176.index'));
    }
}
