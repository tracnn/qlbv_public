<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\DoseSanityRule;

class DoseSanityRuleTest extends TestCase
{
    public function test_lech_so_luong_la_bat_thuong()
    {
        $r = new DoseSanityRule();
        // 2 vien/ngay * 5 ngay = 10, nhung amount = 8 => lech
        $this->assertTrue($r->isMismatch(1, 0, 1, 0, 5, 8));
    }

    public function test_khop_so_luong_khong_bat_thuong()
    {
        $r = new DoseSanityRule();
        // 2/ngay * 5 = 10 == amount 10
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 5, 10));
    }

    public function test_thieu_du_lieu_thi_khong_fire()
    {
        $r = new DoseSanityRule();
        $this->assertFalse($r->isMismatch(0, 0, 0, 0, 5, 8)); // khong co lieu buoi
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 0, 8)); // khong co so ngay
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 5, 0)); // khong co so luong
    }
}
