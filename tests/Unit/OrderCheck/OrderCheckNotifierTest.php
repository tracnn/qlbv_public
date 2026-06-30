<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\OrderCheckNotifier;

class OrderCheckNotifierTest extends TestCase
{
    public function test_nguong_warning_lay_warning_va_critical()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('warning');
        $this->assertContains('warning', $s);
        $this->assertContains('critical', $s);
        $this->assertNotContains('info', $s);
    }

    public function test_nguong_critical_chi_lay_critical()
    {
        $n = new OrderCheckNotifier();
        $this->assertSame(['critical'], array_values($n->severitiesToNotify('critical')));
    }

    public function test_nguong_info_lay_tat_ca()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('info');
        $this->assertContains('info', $s);
        $this->assertContains('warning', $s);
        $this->assertContains('critical', $s);
    }

    public function test_nguong_la_default_warning_neu_khong_hop_le()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('xyz');
        $this->assertContains('warning', $s);
        $this->assertNotContains('info', $s);
    }
}
