<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Chan trang (phien ban + dong ban quyen) phai doc tu cau hinh, khong chot cung trong blade.
 *
 * Truoc day 'footer' KHONG ton tai trong config/adminlte.php nen blade luon roi ve chuoi mac
 * dinh viet thang trong no - nhin thi tuong cau hinh duoc, thuc te thi khong. Con phien ban
 * thi chot cung hoan toan.
 */
class ChanTrangTheoCauHinhTest extends TestCase
{
    const BLADE = 'resources/views/vendor/adminlte/page.blade.php';

    protected function maBlade()
    {
        return file_get_contents(base_path(self::BLADE));
    }

    /** @test */
    public function cau_hinh_co_du_hai_khoa()
    {
        $this->assertNotNull(config('adminlte.version'), 'Thieu khoa adminlte.version');
        $this->assertNotNull(config('adminlte.footer'), 'Thieu khoa adminlte.footer');
        $this->assertNotSame('', trim(config('adminlte.version')));
        $this->assertNotSame('', trim(config('adminlte.footer')));
    }

    /** @test */
    public function blade_doc_phien_ban_tu_cau_hinh()
    {
        $this->assertContains("config('adminlte.version')", $this->maBlade(),
            'Phien ban phai doc tu cau hinh');
    }

    /**
     * Chot rieng: khong duoc chot cung so phien ban trong blade nua. Neu ai do viet lai vao
     * thi doi phien ban se phai sua hai cho, va se co luc hai cho lech nhau.
     */
    /** @test */
    public function blade_khong_con_chot_cung_so_phien_ban()
    {
        $this->assertNotRegExp('~<b>Version</b>\s+[0-9]~', $this->maBlade(),
            'Blade con chot cung so phien ban');
    }

    /** @test */
    public function blade_doc_dong_ban_quyen_tu_cau_hinh()
    {
        $this->assertContains("config('adminlte.footer'", $this->maBlade());
    }

    /**
     * Chan trang render ra phai chua dung gia tri cau hinh, khong phai gia tri mac dinh nao khac.
     */
    /** @test */
    public function chan_trang_render_theo_cau_hinh()
    {
        $ra = $this->renderChanTrang();

        $this->assertContains((string) config('adminlte.version'), $ra);
        $this->assertContains((string) config('adminlte.footer'), $ra);
    }

    /** @test */
    public function doi_cau_hinh_thi_chan_trang_doi_theo()
    {
        config([
            'adminlte.version' => '9999.12.31.7',
            'adminlte.footer' => 'Copyright by Benh vien Thu Nghiem',
        ]);

        $ra = $this->renderChanTrang();

        $this->assertContains('9999.12.31.7', $ra);
        $this->assertContains('Benh vien Thu Nghiem', $ra);
    }

    /** Render rieng khoi <footer> cua blade that. */
    protected function renderChanTrang()
    {
        preg_match('~<footer class="main-footer">(.*?)</footer>~s', $this->maBlade(), $m);

        $this->assertNotEmpty($m, 'Khong tim thay khoi footer trong blade');

        $php = app('blade.compiler')->compileString($m[1]);

        ob_start();
        eval('?>' . $php);

        return ob_get_clean();
    }
}
