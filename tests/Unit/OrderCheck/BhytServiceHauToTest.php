<?php

namespace Tests\Unit\OrderCheck;

use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ma DVKT BHYT co hau to _XX: danh muc BHXH chi liet ke ma goc cho _TB (prod 14/10/2026:
 * 18.0593.0059_TB bi bao "khong co trong danh muc"). Tra nguyen van truoc, roi ma goc.
 *
 * Dung SQLite bo nho (bang danh muc rong) vi CatalogLookup::nap() luon truy van CSDL - tren
 * may dev appuser khong vao duoc MySQL local nen BhytRuleTest dang do san vi ly do do.
 */
class BhytServiceHauToTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        config(['order_check.bhyt_patient_type_ids' => '1']);
        $this->bootXml3176Sqlite([
            '2024_06_12_142339_create_danh_muc_thuoc_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
        ]);
    }

    private function ctx(array $dv)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->services = $dv;

        return $c;
    }

    private function dv($id, $maBhyt, $loai = 2)
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = 'DV' . $id;
        $s->serviceName = 'DV ' . $id;
        $s->patientTypeId = 1;
        $s->bhytCode = $maBhyt;
        $s->serviceTypeId = $loai;
        $s->tdlIntructionTime = 20261001080000;

        return $s;
    }

    /** @test */
    public function ma_dich_vu_co_hau_to_thi_bo_hau_to_tra_ma_goc()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest(['18.0593.0059', '28.0161.0576_GT']);

        $vi = (new BhytServiceCatalogRule($lk))->check($this->ctx([
            $this->dv(1, '18.0593.0059_TB'),   // ma goc co -> khong bao
            $this->dv(2, '28.0161.0576_GT'),   // nguyen van co -> khong bao
            $this->dv(3, '99.9999.9999_TB'),   // ca hai khong co -> vi pham
            $this->dv(4, '18.0593.0059'),      // khong hau to, co -> khong bao
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals(3, $vi[0]->orderRefId);
    }

    /** @test */
    public function quy_tac_thuoc_khong_bo_hau_to()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest(['40.644']);

        $vi = (new BhytDrugCatalogRule($lk))->check($this->ctx([$this->dv(1, '40.644_TB', 6)]));

        $this->assertCount(1, $vi);
    }
}
