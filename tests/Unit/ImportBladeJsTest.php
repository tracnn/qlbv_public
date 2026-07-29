<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Man nhap danh muc chua nhieu JavaScript viet thang trong blade.
 *
 * Ly do ton tai: mot loi cu phap JS lam CA khoi script khong chay - ke ca dong
 * `Dropzone.autoDiscover = false` - nen Dropzone roi ve mac dinh va bang "Ho so da tai
 * len" trong rong, ma khong co thong bao nao. Da xay ra that: mot chuoi bi xuong dong
 * that o giua lam hong toan bo man hinh.
 *
 * Blade khong duoc bien dich o day: phan JS nam ngoai moi directive nen doc thang tu tep
 * la du, va nhu vay test khong phu thuoc trang thai ung dung.
 */
class ImportBladeJsTest extends TestCase
{
    /** Cac blade co khoi script can kiem */
    private function bladeCanKiem()
    {
        return [
            resource_path('views/category/bhyt/import.blade.php'),
        ];
    }

    /** @return string[] noi dung tung khoi <script> khong co thuoc tinh src */
    private function khoiScript($duongDan)
    {
        $html = file_get_contents($duongDan);

        preg_match_all('#<script>(.*?)</script>#s', $html, $m);

        return $m[1];
    }

    /** @test */
    public function man_nhap_danh_muc_co_khoi_script_de_kiem()
    {
        foreach ($this->bladeCanKiem() as $tep) {
            $this->assertFileExists($tep);
            $this->assertNotEmpty($this->khoiScript($tep), "Khong tach duoc khoi script tu $tep");
        }
    }

    /** @test */
    public function javascript_trong_blade_dung_cu_phap()
    {
        $node = $this->timNode();

        if ($node === null) {
            $this->markTestSkipped('Khong co node de kiem cu phap JavaScript');
        }

        foreach ($this->bladeCanKiem() as $tep) {
            $js = implode(PHP_EOL, $this->khoiScript($tep));
            $tam = storage_path('app/kiem_js_' . md5($tep) . '.js');

            file_put_contents($tam, $js);

            try {
                $ra = [];
                $ma = 0;
                exec(escapeshellarg($node) . ' --check ' . escapeshellarg($tam) . ' 2>&1', $ra, $ma);

                $this->assertSame(0, $ma,
                    'JavaScript trong ' . basename($tep) . ' sai cu phap:' . PHP_EOL . implode(PHP_EOL, $ra));
            } finally {
                @unlink($tam);
            }
        }
    }

    private function timNode()
    {
        $ra = [];
        $ma = 0;

        exec('node --version 2>&1', $ra, $ma);

        return $ma === 0 ? 'node' : null;
    }
}
