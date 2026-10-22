<?php

namespace Tests\Unit\SinhXml3176;

use Tests\TestCase;

/**
 * Module sinh moi khong duoc dung lai ma cua module Xml3176 cu (nap tu tep).
 */
class CachLyModuleTest extends TestCase
{
    /** @test */
    public function test_khong_dung_module_xml3176()
    {
        $goc = base_path();
        $tep = glob($goc . '/app/Console/Commands/SinhXml3176*.php') ?: [];

        $thuMuc = $goc . '/app/Services/SinhXml3176';
        if (is_dir($thuMuc)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($thuMuc, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                    $tep[] = $f->getPathname();
                }
            }
        }

        $this->assertNotEmpty($tep);
        foreach ($tep as $duongDan) {
            $this->assertSame(
                0,
                preg_match('/\bApp\\\\(Services\\\\Xml3176|Models\\\\BHYT\\\\Xml3176)/', file_get_contents($duongDan)),
                'Vi pham cach ly: ' . $duongDan
            );
        }
    }
}
