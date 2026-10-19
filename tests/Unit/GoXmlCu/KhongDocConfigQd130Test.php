<?php

namespace Tests\Unit\GoXmlCu;

use App\Exports\HeinCardErrorExport;
use Tests\Support\LocComment;
use Tests\TestCase;

/**
 * Ma GIU LAI (khong thuoc module Qd130 se bi xoa) khong duoc doc config('qd130xml...'):
 * khi xoa config/qd130xml.php, config() tra null/mang rong IM LANG - jobKtTheBHYT se coi
 * moi the la hop le ma khong bao loi gi.
 */
class KhongDocConfigQd130Test extends TestCase
{
    use LocComment;

    /** File cua module Qd130 - Task 7 xoa, duoc phep doc config qd130xml toi luc do. */
    const DANH_SACH_XOA_QD130 = [
        'app/Http/Controllers/BHYT/BHYTQd130Controller.php',
        'app/Console/Commands/XML130Import.php',
        'app/Console/Commands/sendQd130XmlErrors.php',
    ];

    private function laFileQd130(string $tuongDoi): bool
    {
        return in_array($tuongDoi, self::DANH_SACH_XOA_QD130, true)
            || preg_match('#/Qd130[^/]*\.php$#', $tuongDoi);
    }

    /** @test */
    public function ma_giu_lai_khong_doc_config_qd130xml()
    {
        $viPham = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('app')));

        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $tuongDoi = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            if ($this->laFileQd130($tuongDoi)) {
                continue;
            }
            if (preg_match('/config\(\s*[\'"]qd130xml\./', $this->maKhongComment($file->getPathname()))) {
                $viPham[] = $tuongDoi;
            }
        }

        $this->assertSame([], $viPham, "Con doc config qd130xml:\n" . implode("\n", $viPham));
    }

    /** @test */
    public function heinCardErrorExport_mac_dinh_doc_xml3176()
    {
        $thamSo = (new \ReflectionMethod(HeinCardErrorExport::class, '__construct'))->getParameters();
        $khoa = collect($thamSo)->first(function ($p) {
            return $p->getName() === 'khoaCauHinh';
        });

        $this->assertSame('xml3176', $khoa->getDefaultValue());
    }
}
