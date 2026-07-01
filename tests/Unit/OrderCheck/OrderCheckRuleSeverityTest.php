<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Http\Controllers\KHTH\OrderCheckRuleController;

class OrderCheckRuleSeverityTest extends TestCase
{
    public function test_severity_whitelist_dung_3_muc()
    {
        $s = OrderCheckRuleController::SEVERITIES;
        $this->assertSame(['info', 'warning', 'critical'], $s);
        $this->assertContains('critical', $s);
        $this->assertNotContains('xxx', $s);
    }
}
