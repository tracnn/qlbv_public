<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\AgeRestrictionRule;

class AgeRestrictionRuleTest extends TestCase
{
    public function test_duoi_tuoi_toi_thieu_la_vi_pham()
    {
        $r = new AgeRestrictionRule();
        // sinh 2020-01-01, mốc 2026-06-30 => 6 tuoi; age_from=16 => vi pham
        $this->assertTrue($r->outOfRange('20200101000000', 16, null, '20260630'));
    }

    public function test_tren_tuoi_toi_da_la_vi_pham()
    {
        $r = new AgeRestrictionRule();
        // sinh 1950 => 76 tuoi; age_to=6 => vi pham
        $this->assertTrue($r->outOfRange('19500101000000', null, 6, '20260630'));
    }

    public function test_trong_khoang_khong_vi_pham()
    {
        $r = new AgeRestrictionRule();
        $this->assertFalse($r->outOfRange('20000101000000', 16, 60, '20260630')); // 26 tuoi
    }

    public function test_thieu_ngay_sinh_thi_bo_qua()
    {
        $r = new AgeRestrictionRule();
        $this->assertFalse($r->outOfRange('00000000000000', 16, null, '20260630'));
        $this->assertFalse($r->outOfRange(null, 16, null, '20260630'));
    }
}
