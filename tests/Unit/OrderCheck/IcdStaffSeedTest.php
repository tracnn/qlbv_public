<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use Tests\Support\LocComment;

class IcdStaffSeedTest extends TestCase
{
    use LocComment;

    private function nguonSeed()
    {
        $file = glob(database_path('migrations/*seed_order_check_icd_staff_rules.php'));
        $this->assertNotEmpty($file, 'Chua co migration seed');

        return $this->maKhongComment($file[0]);
    }

    /** @test */
    public function ba_quy_tac_deu_co_trong_seed()
    {
        $ma = $this->nguonSeed();

        foreach ([
            'A_ICD_NOT_IN_CATALOG',
            'A_ICD_YHCT_NOT_IN_CATALOG',
            'A_STAFF_CERT_NOT_IN_CATALOG',
        ] as $code) {
            $this->assertContains($code, $ma, "Thieu quy tac $code");
        }

        foreach ([
            'IcdNotInCatalogRule',
            'IcdYhctNotInCatalogRule',
            'StaffCertNotInCatalogRule',
        ] as $t) {
            $this->assertContains($t, $ma, "Thieu rule_type $t");
        }
    }

    /** @test */
    public function ba_quy_tac_seed_o_trang_thai_TAT()
    {
        $ma = $this->nguonSeed();

        $this->assertContains("'is_active' => false", $ma);
        $this->assertNotContains("'is_active' => true", $ma, 'Co quy tac seed o trang thai BAT');
    }

    /** @test */
    public function seed_khong_ghi_de_quy_tac_da_ton_tai()
    {
        $ma = $this->nguonSeed();

        $this->assertContains('exists()', $ma, 'Seed khong kiem quy tac da ton tai truoc khi chen');
    }
}
