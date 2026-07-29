<?php

namespace Tests\Unit;

use DB;
use App\Services\BHYT\LocCoSo;
use Tests\TestCase;

class LocCoSoTest extends TestCase
{
    protected function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    /** @test */
    public function ma_rong_thi_khong_loc()
    {
        $this->assertSame('', LocCoSo::maHopLe('', $this->danhSach()));
        $this->assertSame('', LocCoSo::maHopLe(null, $this->danhSach()));
    }

    /** @test */
    public function ma_ngoai_danh_sach_thi_khong_loc()
    {
        $this->assertSame('', LocCoSo::maHopLe('99999', $this->danhSach()));
    }

    /** @test */
    public function ma_hop_le_thi_giu_nguyen()
    {
        $this->assertSame('01929', LocCoSo::maHopLe('01929', $this->danhSach()));
        $this->assertSame('01929', LocCoSo::maHopLe('  01929  ', $this->danhSach()));
    }

    /** @test */
    public function ap_ma_hop_le_thi_them_dieu_kien()
    {
        $q = DB::table('xml3176_xml1s');
        LocCoSo::ap($q, '01929', $this->danhSach());

        $this->assertContains('ma_cskcb', $q->toSql());
        $this->assertContains('01929', $q->getBindings());
    }

    /** @test */
    public function ap_ma_khong_hop_le_thi_khong_them_gi()
    {
        $q = DB::table('xml3176_xml1s');
        LocCoSo::ap($q, '99999', $this->danhSach());

        $this->assertNotContains('ma_cskcb', $q->toSql());
        $this->assertSame([], $q->getBindings());
    }
}
