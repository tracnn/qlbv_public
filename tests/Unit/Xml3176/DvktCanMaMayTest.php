<?php

namespace Tests\Unit\Xml3176;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktCanMaMayTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var CommonValidationService */
    private $sv;

    protected function setUp()
    {
        parent::setUp();

        $this->bootXml3176Sqlite(['2026_09_10_100000_create_dvkt_can_ma_may_table.php']);

        $this->sv = app(CommonValidationService::class);
    }

    private function nap(array $dong)
    {
        DB::table('dvkt_can_ma_may')->insert($dong);
    }

    /** @test */
    public function danh_muc_rong_thi_bao_la_chua_co()
    {
        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function danh_muc_chi_co_dong_nghi_huu_van_coi_la_chua_co()
    {
        // Dong is_active = 0 la du lieu cua lan nap truoc, khong duoc tinh
        $this->nap([['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 0]]);

        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function co_dong_dang_dung_thi_bao_la_da_co()
    {
        $this->nap([['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 1]]);

        $this->assertTrue($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function ma_co_trong_danh_muc_thi_dung()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertTrue($this->sv->isDvktCanMaMay('02.0261.0319'));
    }

    /** @test */
    public function ma_co_hau_to_khop_qua_ma_goc()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertTrue($this->sv->isDvktCanMaMay('02.0261.0319_TB'));
    }

    /** @test */
    public function ma_khong_co_trong_danh_muc_thi_sai()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertFalse($this->sv->isDvktCanMaMay('23.0020.1493'));
    }

    /** @test */
    public function dong_nghi_huu_khong_duoc_tinh()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 0]]);

        $this->assertFalse($this->sv->isDvktCanMaMay('02.0261.0319'));
    }

    /** @test */
    public function ma_rong_thi_sai()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertFalse($this->sv->isDvktCanMaMay(''));
        $this->assertFalse($this->sv->isDvktCanMaMay(null));
    }
}
