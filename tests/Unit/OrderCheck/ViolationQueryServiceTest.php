<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\ViolationQueryService;

class ViolationQueryServiceTest extends TestCase
{
    public function test_status_hop_le_duoc_chap_nhan()
    {
        $svc = new ViolationQueryService();
        $this->assertTrue($svc->isValidUpdateStatus('processed'));
        $this->assertTrue($svc->isValidUpdateStatus('false_positive'));
        $this->assertTrue($svc->isValidUpdateStatus('seen'));
    }

    public function test_status_khong_hop_le_bi_tu_choi()
    {
        $svc = new ViolationQueryService();
        $this->assertFalse($svc->isValidUpdateStatus('new'));   // 'new' do engine dat, khong cho set tay
        $this->assertFalse($svc->isValidUpdateStatus('xyz'));
        $this->assertFalse($svc->isValidUpdateStatus(''));
    }
}
