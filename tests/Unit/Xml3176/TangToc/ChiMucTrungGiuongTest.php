<?php

namespace Tests\Unit\Xml3176\TangToc;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class ChiMucTrungGiuongTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const TEN = 'xml3176_xml3s_giuong_khoa_th_yl_index';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
        ]);
    }

    protected function mig()
    {
        require_once base_path('database/migrations/2026_10_15_120000_them_chi_muc_trung_giuong_xml3176_xml3s.php');

        return new \ThemChiMucTrungGiuongXml3176Xml3s();
    }

    protected function tenChiMuc()
    {
        return array_map(function ($d) {
            return $d->name;
        }, DB::select("PRAGMA index_list('xml3176_xml3s')"));
    }

    /** @test */
    public function chay_lai_khong_loi()
    {
        $mig = $this->mig();
        $mig->up();
        $mig->up();

        $this->assertSame(1, count(array_keys($this->tenChiMuc(), self::TEN)));
    }

    /** @test */
    public function down_xoa_chi_muc()
    {
        $mig = $this->mig();
        $mig->up();
        $this->assertContains(self::TEN, $this->tenChiMuc());

        $mig->down();
        $mig->down();
        $this->assertNotContains(self::TEN, $this->tenChiMuc());
    }
}
