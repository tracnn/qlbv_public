<?php

namespace Tests\Unit;

use DB;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\ViolationContext;
use Tests\TestCase;

class ViPhamMaCoSoTest extends TestCase
{
    /** @test */
    public function bang_vi_pham_co_cot_ma_cskcb()
    {
        $co = false;

        foreach (DB::select('SHOW COLUMNS FROM order_check_violations') as $c) {
            if ($c->Field === 'ma_cskcb') {
                $co = true;
                break;
            }
        }

        $this->assertTrue($co, 'Bang order_check_violations thieu cot ma_cskcb');
    }

    /** @test */
    public function violation_context_giu_duoc_ma_cskcb()
    {
        $c = ViolationContext::make(['ma_cskcb' => '01929']);

        $this->assertSame('01929', $c->maCskcb);
    }

    /** @test */
    public function khong_truyen_ma_cskcb_thi_la_null()
    {
        $c = ViolationContext::make([]);

        $this->assertNull($c->maCskcb);
    }

    /**
     * Mat xich de quen nhat: fromOrderContext la mot danh sach khoa CHEP TAY. Them truong
     * vao ViolationContext ma quen them o day thi ma co so im lang khong bao gio duoc ghi,
     * va bo loc se rong tren moi vi pham moi.
     */
    /** @test */
    public function from_order_context_chuyen_duoc_ma_cskcb()
    {
        $o = new OrderContext();
        $o->maCskcb = '37470';

        $c = ViolationContext::fromOrderContext($o);

        $this->assertSame('37470', $c->maCskcb);
    }
}
