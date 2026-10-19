<?php

namespace Tests\Unit\GoXmlCu;

use App\Services\BHYT\BacSiYLenhQuery;
use Tests\TestCase;

/**
 * Bao cao "Bac si y lenh" doc bang XML3176 (khong con qd130_*). Loc theo ngay vao/ra/thanh
 * toan phai di qua xml3176_xml1s - xml2/xml3 khong co cac cot do.
 */
class BacSiYLenhQueryTest extends TestCase
{
    private function sql(string $dateType): string
    {
        return str_replace(['`', '"'], '', BacSiYLenhQuery::build($dateType, '2026-09-01', '2026-09-30')->toSql());
    }

    /** @test */
    public function doc_bang_xml3176_xml2_va_xml3()
    {
        $sql = $this->sql('date_yl');

        $this->assertContains('xml3176_xml2s', $sql);
        $this->assertContains('xml3176_xml3s', $sql);
        $this->assertNotContains('qd130_', $sql);
    }

    /** @test */
    public function loc_ngay_ra_qua_xml1()
    {
        $sql = $this->sql('date_out');

        $this->assertContains('xml3176_xml1s', $sql);
        $this->assertContains('ngay_ra', $sql);
        $this->assertNotContains('q.ngay_ra', $sql);
    }

    /** @test */
    public function loc_ngay_yl_khong_cham_xml1()
    {
        $sql = $this->sql('date_yl');

        $this->assertNotContains('xml3176_xml1s', $sql);
        $this->assertContains('q.ngay_yl', $sql);
    }

    /** @test */
    public function ngay_yl_doi_sang_dang_YmdHi()
    {
        $b = BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30')->getBindings();

        $this->assertSame(['202609010000', '202609302359', '202609010000', '202609302359'], $b);
    }

    /** @test */
    public function cot_ma_khoa_chon_duoc()
    {
        $sql = str_replace(['`', '"'], '', BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30', 'q.ma_khoa')->toSql());

        $this->assertContains('q.ma_khoa', $sql);
    }
}
