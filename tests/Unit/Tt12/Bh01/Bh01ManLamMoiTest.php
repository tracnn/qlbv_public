<?php

namespace Tests\Unit\Tt12\Bh01;

use Tests\TestCase;

/**
 * Bang "Cac ban da lap" o man 01/BH KHONG tu lam moi theo hen gio: bang nhay va mat vi tri
 * dang xem moi 5 giay trong luc cho ky. Nguoi dung tu bam nut "Lam moi" de xem trang thai
 * ky tu hang doi.
 */
class Bh01ManLamMoiTest extends TestCase
{
    private function view()
    {
        return file_get_contents(resource_path('views/bhyt/tt12/bh01/index.blade.php'));
    }

    /** @test */
    public function khong_con_hen_gio_tu_lam_moi()
    {
        $v = $this->view();

        $this->assertNotContains('henLamMoi', $v);
        $this->assertNotContains('CHO_KY_TOI_DA_MS', $v);
        $this->assertSame(0, preg_match('/setTimeout\([^;]*ajax\.reload/s', $v), 'Con setTimeout goi ajax.reload');
        $this->assertSame(0, preg_match('/setInterval\([^;]*ajax\.reload/s', $v), 'Con setInterval goi ajax.reload');
    }

    /** @test */
    public function co_nut_lam_moi_tai_lai_giu_trang()
    {
        $v = $this->view();

        $this->assertContains('id="bh01-btn-lam-moi"', $v);
        $this->assertContains('Làm mới', $v);
        $this->assertSame(1, preg_match(
            "/\\$\\('#bh01-btn-lam-moi'\\)\\.on\\('click',[^}]*bang\\.ajax\\.reload\\(null, false\\)/s", $v),
            'Nut Lam moi phai tai lai bang va giu trang dang xem');
    }
}
