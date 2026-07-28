<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use Tests\Support\LocComment;

class BhytSeedTest extends TestCase
{
    use LocComment;

    private function nguonSeed()
    {
        $file = glob(database_path('migrations/*seed_order_check_bhyt_catalog_rules.php'));
        $this->assertNotEmpty($file, 'Chua co migration seed');

        return $this->maKhongComment($file[0]);
    }

    /** @test */
    public function bon_quy_tac_moi_deu_co_trong_seed()
    {
        $ma = $this->nguonSeed();

        foreach ([
            'A_BHYT_CODE_MISSING',
            'A_BHYT_SERVICE_NOT_IN_CATALOG',
            'A_BHYT_DRUG_NOT_IN_CATALOG',
            'A_BHYT_SUPPLY_NOT_IN_CATALOG',
        ] as $code) {
            $this->assertContains($code, $ma, "Thieu quy tac $code trong seed");
        }
    }

    /** @test */
    public function bon_quy_tac_moi_seed_o_trang_thai_TAT()
    {
        // Khong do duoc ti le khop that (ba bang danh muc tren DB dev deu 0 dong), va
        // 21.778 dich vu HIS chi 10.552 khai ma BHXH. Bat san la co the do ra hang nghin
        // vi pham ngay dau.
        $ma = $this->nguonSeed();

        $this->assertContains("'is_active' => false", $ma);
        $this->assertNotContains("'is_active' => true", $ma,
            'Co quy tac seed o trang thai BAT');
    }

    /** @test */
    public function seed_khong_ghi_de_quy_tac_da_ton_tai()
    {
        // Chay lai migration khong duoc dat lai is_active ve false neu chu dau tu da bat.
        $ma = $this->nguonSeed();

        $this->assertContains('exists()', $ma,
            'Seed khong kiem quy tac da ton tai truoc khi chen');
    }

    private function nguonSeedTen()
    {
        $file = glob(database_path('migrations/*seed_order_check_bhyt_name_rules.php'));
        $this->assertNotEmpty($file, 'Chua co migration seed quy tac ten');

        return $this->maKhongComment($file[0]);
    }

    /** @test */
    public function ba_quy_tac_ten_deu_co_trong_seed()
    {
        $ma = $this->nguonSeedTen();

        foreach ([
            'A_BHYT_SERVICE_NAME_MISMATCH',
            'A_BHYT_DRUG_NAME_MISMATCH',
            'A_BHYT_SUPPLY_NAME_MISMATCH',
        ] as $code) {
            $this->assertContains($code, $ma, "Thieu quy tac $code trong seed");
        }

        foreach ([
            'BhytServiceNameRule',
            'BhytDrugNameRule',
            'BhytSupplyNameRule',
        ] as $t) {
            $this->assertContains($t, $ma, "Thieu rule_type $t trong seed");
        }
    }

    /** @test */
    public function ba_quy_tac_ten_seed_o_trang_thai_TAT()
    {
        // Phep so ten la TUYET DOI va ba bang danh muc tren DB dev deu 0 dong, khong do
        // duoc ti le lech ten that truoc khi trien khai.
        $ma = $this->nguonSeedTen();

        $this->assertContains("'is_active' => false", $ma);
        $this->assertNotContains("'is_active' => true", $ma,
            'Co quy tac ten seed o trang thai BAT');
    }

    /** @test */
    public function seed_quy_tac_ten_khong_ghi_de_quy_tac_da_ton_tai()
    {
        $ma = $this->nguonSeedTen();

        $this->assertContains('exists()', $ma,
            'Seed khong kiem quy tac da ton tai truoc khi chen');
    }

    /** @test */
    public function his_order_source_lay_them_loai_dich_vu_va_ten_bhyt()
    {
        // Thieu hai cot nay thi quy tac ten khong co gi de so, va quy tac ma khong loc duoc
        // theo loai - do duoc 53.288 dong bat oan moi tuan.
        $ma = $this->maKhongComment(app_path('Services/OrderCheck/HisOrderSource.php'));

        $this->assertContains('sv.service_type_id', $ma);
        $this->assertContains('sv.hein_service_bhyt_name', $ma);
        $this->assertContains('serviceTypeId', $ma);
        $this->assertContains('bhytName', $ma);
    }

    /** @test */
    public function order_service_co_hai_thuoc_tinh_moi()
    {
        $s = new \App\Services\OrderCheck\Support\OrderService();

        $this->assertTrue(property_exists($s, 'serviceTypeId'));
        $this->assertTrue(property_exists($s, 'bhytName'));
    }
}
