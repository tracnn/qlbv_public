<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01ChuoiChung;
use App\Services\Tt12\Bh01\Bh01DocExcel;
use Box\Spout\Reader\XLSX\Creator\HelperFactory;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;
use Tests\TestCase;

/**
 * Do that 19/10/2026: tep 01/BH 21.125 dong do chinh chuc nang Xuat Excel sinh ra co 411.502 chuoi
 * dung chung. Spout uoc 12 KB/chuoi (4,9 GB) nen chon luu chuoi ra tep tam: 40 giay cho 14.635
 * dong, nhap hai luot vuot 60 giay -> HTTP 500. Giu trong RAM thuc te: 8,5 giay, dinh 28 MB.
 */
class Bh01ChuoiChungTest extends TestCase
{
    private function chon($soChuoi, $gioiHan)
    {
        $f = new class($gioiHan) extends Bh01ChuoiChung {
            private $gh;
            public function __construct($gh) { $this->gh = $gh; }
            protected function getMemoryLimitFromIni() { return $this->gh; }
        };

        return $f->createBestCachingStrategy($soChuoi, sys_get_temp_dir(), new HelperFactory());
    }

    /** @test */
    public function tep_21_nghin_dong_giu_chuoi_trong_ram_voi_128m()
    {
        $this->assertInstanceOf(InMemoryStrategy::class, $this->chon(411502, '128M'));
    }

    /** @test */
    public function qua_nhieu_chuoi_so_voi_ram_thi_luu_ra_tep()
    {
        $this->assertInstanceOf(FileBasedStrategy::class, $this->chon(2000000, '128M'));
    }

    /** @test */
    public function khong_biet_so_chuoi_thi_luu_ra_tep()
    {
        $this->assertInstanceOf(FileBasedStrategy::class, $this->chon(null, '128M'));
    }

    /** @test */
    public function bo_doc_excel_dung_chien_luoc_rieng()
    {
        $doc = new \ReflectionMethod(Bh01DocExcel::class, 'taoBoDoc');
        $doc->setAccessible(true);
        $reader = $doc->invoke(new Bh01DocExcel());

        $mf = new \ReflectionProperty(get_class($reader), 'managerFactory');
        $mf->setAccessible(true);
        $csf = new \ReflectionProperty(get_class($mf->getValue($reader)), 'cachingStrategyFactory');
        $csf->setAccessible(true);

        $this->assertInstanceOf(Bh01ChuoiChung::class, $csf->getValue($mf->getValue($reader)));
    }
}
