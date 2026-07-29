<?php

namespace Tests\Unit;

use App\Services\OrderCheck\ViolationQueryService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ViolationQueryCoSoTest extends TestCase
{
    protected function sql(array $tham)
    {
        $q = (new ViolationQueryService())->filtered(Request::create('/', 'GET', $tham));

        return ['sql' => $q->toSql(), 'bind' => $q->getBindings()];
    }

    /** @test */
    public function khong_chon_co_so_thi_khong_loc()
    {
        $r = $this->sql([]);

        $this->assertNotContains('ma_cskcb', $r['sql']);
    }

    /** @test */
    public function chon_co_so_rong_thi_khong_loc()
    {
        // "Tat ca co so" gui len chuoi rong; filled() phai coi day la khong loc.
        $r = $this->sql(['ma_cskcb' => '']);

        $this->assertNotContains('ma_cskcb', $r['sql']);
    }

    /** @test */
    public function chon_co_so_thi_loc_dung_ma_do()
    {
        $r = $this->sql(['ma_cskcb' => '01929']);

        $this->assertContains('ma_cskcb', $r['sql']);
        $this->assertContains('01929', $r['bind']);
    }
}
