<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\HisOrderSource;

class HisOrderSourceInitialWatermarkTest extends TestCase
{
    private function source()
    {
        return new HisOrderSource();
    }

    public function test_map_moc_khoi_tao_dung_bang_cot_truong()
    {
        $s = $this->source();
        $this->assertSame(
            ['table' => 'his_service_req', 'column' => 'modify_time', 'field' => 'last_modify_time'],
            $s->initTargetFor('his_service_req')
        );
        $this->assertSame(
            ['table' => 'his_medicine_interactive', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_medicine_interactive')
        );
        $this->assertSame(
            ['table' => 'his_exp_mest_medicine', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_exp_mest_medicine')
        );
        $this->assertSame(
            ['table' => 'his_sere_serv', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_sere_serv_restriction')
        );
    }

    public function test_key_la_tra_null_va_initialWatermark_tra_zeros_khong_cham_db()
    {
        $s = $this->source();
        $this->assertNull($s->initTargetFor('khong_ton_tai'));
        $this->assertSame(
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0],
            $s->initialWatermark('khong_ton_tai')
        );
    }
}
