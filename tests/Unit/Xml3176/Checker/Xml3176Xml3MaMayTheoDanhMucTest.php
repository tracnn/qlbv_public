<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * "Thieu ma may" chi can cu danh muc DVKT can ma may (danh muc tra cuu), khong con lui ve
 * cach loc theo nhom 1/2/3 cu.
 */
class Xml3176Xml3MaMayTheoDanhMucTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_10_100000_create_dvkt_can_ma_may_table.php']);
    }

    private function canMaMay(array $attrs): bool
    {
        $checker = $this->makeChecker(Xml3176Xml3Checker::class);
        $d = new Xml3176Xml3();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }

        return $this->invokePrivate($checker, 'canMaMay', $d);
    }

    private function nap(string $ma, int $dangDung = 1): void
    {
        DB::table('dvkt_can_ma_may')->insert(['ma_dvkt' => $ma, 'is_active' => $dangDung]);
    }

    /** @test */
    public function ma_co_trong_danh_muc_thi_can_ma_may_du_thuoc_nhom_nao()
    {
        $this->nap('18.0001.0001');

        $this->assertTrue($this->canMaMay(['ma_nhom' => '18', 'ma_dich_vu' => '18.0001.0001']));
    }

    /** @test */
    public function nhom_1_2_3_khong_co_trong_danh_muc_thi_khong_can()
    {
        $this->nap('18.0001.0001');

        $this->assertFalse($this->canMaMay(['ma_nhom' => '1', 'ma_dich_vu' => '22.0001.0001']));
        $this->assertFalse($this->canMaMay(['ma_nhom' => '2', 'ma_dich_vu' => '22.0001.0002']));
        $this->assertFalse($this->canMaMay(['ma_nhom' => '3', 'ma_dich_vu' => '22.0001.0003']));
    }

    /** @test */
    public function danh_muc_rong_thi_khong_lui_ve_quy_tac_theo_nhom()
    {
        $this->assertFalse($this->canMaMay(['ma_nhom' => '1', 'ma_dich_vu' => '22.0001.0001']));
    }

    /** @test */
    public function ma_hau_to_khop_qua_ma_goc()
    {
        $this->nap('02.0261.0319');

        $this->assertTrue($this->canMaMay(['ma_nhom' => '18', 'ma_dich_vu' => '02.0261.0319_TB']));
    }

    /** @test */
    public function cau_hinh_nhom_cu_da_bo()
    {
        $this->assertNull(config('xml3176.xml3.service_groups_requiring_machine'));
    }
}
