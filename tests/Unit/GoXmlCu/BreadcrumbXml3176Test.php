<?php

namespace Tests\Unit\GoXmlCu;

use DaveJamesMiller\Breadcrumbs\Facades\Breadcrumbs;
use Tests\TestCase;

/**
 * Man XML3176 va bao cao y lenh tung render breadcrumb 'bhyt.index' cua module 4210. Go
 * route 4210 ma view con goi thi route('bhyt.index') nem loi - man chinh XML3176 sap 500.
 */
class BreadcrumbXml3176Test extends TestCase
{
    /** View cua 4210/Qd130 - Task 6/7 xoa, duoc phep con tham chieu bhyt.index toi luc do. */
    const VIEW_SE_XOA = [
        'resources/views/bhyt/index.blade.php',
        'resources/views/bhyt/search.blade.php',
        'resources/views/bhyt/check-card.blade.php',
        'resources/views/bhyt/reports/kcb-trai-tuyen.blade.php',
        'resources/views/bhyt/reports/dvkt-co-dieu-kien.blade.php',
        'resources/views/bhyt/reports/thuoc-co-dieu-kien.blade.php',
    ];

    const THU_MUC_SE_XOA = [
        'resources/views/bhyt/detail/',
        'resources/views/bhyt/qd130/',
        'resources/views/system/upload-xml/',
    ];

    private function seXoa(string $tuongDoi): bool
    {
        if (in_array($tuongDoi, self::VIEW_SE_XOA, true)) {
            return true;
        }
        foreach (self::THU_MUC_SE_XOA as $thuMuc) {
            if (strpos($tuongDoi, $thuMuc) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @test */
    public function breadcrumb_xml3176_render_duoc()
    {
        $muc = Breadcrumbs::generate('bhyt.xml3176.index');

        $this->assertSame(route('bhyt.xml3176.index'), $muc->last()->url);
    }

    /** @test */
    public function view_giu_lai_khong_render_bhyt_index()
    {
        $viPham = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $tuongDoi = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            if ($this->seXoa($tuongDoi)) {
                continue;
            }
            if (preg_match('/(render|route)\(\s*[\'"]bhyt\.index[\'"]/', file_get_contents($file->getPathname()))) {
                $viPham[] = $tuongDoi;
            }
        }

        $this->assertSame([], $viPham, "Con tham chieu bhyt.index:\n" . implode("\n", $viPham));
    }
}
