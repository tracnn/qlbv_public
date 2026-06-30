<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\Violation;

class ViolationTest extends TestCase
{
    public function test_dedup_key_ket_hop_rule_ref_va_subkey()
    {
        $v = new Violation('B_TEST', 'service_req', 123, 'msg', ['a' => 1], 'after_out');
        $this->assertSame('B_TEST:service_req:123:after_out', $v->dedupKey());
    }

    public function test_dedup_key_rong_subkey_van_hop_le()
    {
        $v = new Violation('B_TEST', 'treatment', 9, 'msg');
        $this->assertSame('B_TEST:treatment:9:', $v->dedupKey());
    }
}
