<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use Tests\Support\LocComment;
use App\Services\OrderCheck\Support\OrderContext;

class OrderSourceIcdCchnTest extends TestCase
{
    use LocComment;

    /** @test */
    public function order_context_co_bon_truong_moi()
    {
        $c = new OrderContext();

        foreach (['icdSubCode', 'traditionalIcdCode', 'traditionalIcdSubCode', 'requestDiploma'] as $t) {
            $this->assertTrue(property_exists($c, $t), "Thieu thuoc tinh $t");
        }
    }

    /** @test */
    public function his_order_source_lay_ba_cot_icd_va_cchn_bac_si_chi_dinh()
    {
        $ma = $this->maKhongComment(app_path('Services/OrderCheck/HisOrderSource.php'));

        foreach ([
            'sr.icd_sub_code',
            'sr.traditional_icd_code',
            'sr.traditional_icd_sub_code',
        ] as $cot) {
            $this->assertContains($cot, $ma, "Chua select $cot");
        }

        // CCHN bac si chi dinh phai join rieng, khong dung chung alias voi nguoi thuc hien.
        $this->assertContains('request_loginname', $ma);
        $this->assertContains('request_diploma', $ma);
        $this->assertContains('requestDiploma', $ma);
    }
}
