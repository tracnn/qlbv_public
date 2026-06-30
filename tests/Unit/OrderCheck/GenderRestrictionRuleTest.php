<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\GenderRestrictionRule;

class GenderRestrictionRuleTest extends TestCase
{
    public function test_lech_gioi_la_vi_pham()
    {
        $r = new GenderRestrictionRule();
        $this->assertTrue($r->mismatch(2, 1));  // BN nam (2), DV chi cho nu (1)
        $this->assertTrue($r->mismatch(1, 2));
    }

    public function test_dung_gioi_khong_vi_pham()
    {
        $r = new GenderRestrictionRule();
        $this->assertFalse($r->mismatch(1, 1));
        $this->assertFalse($r->mismatch(2, 2));
    }

    public function test_khong_gioi_han_hoac_khong_xac_dinh_thi_bo_qua()
    {
        $r = new GenderRestrictionRule();
        $this->assertFalse($r->mismatch(2, null)); // DV khong gioi han
        $this->assertFalse($r->mismatch(2, 3));    // rang buoc KXD
        $this->assertFalse($r->mismatch(3, 1));    // BN KXD
    }
}
