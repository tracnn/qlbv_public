<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan viec go chan tren khoi cac truy van theo moc id - go la quay lai canh moi luot
 * sap xep toan bo ton, cham dan theo do lon cua ton.
 *
 * Va chan viec ai do "cho dong bo" ma ap nham cua so cho fetchServiceRequests, von dung
 * moc theo modify_time chu khong phai id.
 */
class HisOrderSourceCuaSoTest extends TestCase
{
    use LocComment;

    protected function ma()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Services/OrderCheck/HisOrderSource.php'));
    }

    /** Than cua mot method, cat tu dong khai bao den dau '}' o dau dong cung cap */
    protected function than($tenMethod)
    {
        $ma = $this->ma();
        $vt = strpos($ma, 'function ' . $tenMethod . '(');

        $this->assertNotFalse($vt, "Khong tim thay method $tenMethod");

        $ke = strpos($ma, "\n    public function ", $vt + 10);
        $ke = $ke === false ? strlen($ma) : $ke;

        return substr($ma, $vt, $ke - $vt);
    }

    /** @test */
    public function ba_truy_van_theo_moc_id_deu_co_chan_tren()
    {
        foreach (['fetchInteractions', 'fetchExpMestBatch', 'fetchSereServWithPatient'] as $m) {
            $than = $this->than($m);

            $this->assertContains('cuoiCuaSo', $than,
                "Method $m mat chan tren cua so quet");
        }
    }

    /** @test */
    public function truy_van_theo_thoi_gian_khong_bi_ap_cua_so()
    {
        // fetchServiceRequests dung moc theo modify_time: mot dong cu duoc sua lai se nhay
        // ve cuoi hang doi, nen cua so theo id khong co y nghia o day.
        $than = $this->than('fetchServiceRequests');

        $this->assertNotContains('cuoiCuaSo', $than,
            'fetchServiceRequests khong duoc ap cua so theo id');
    }

    /** @test */
    public function cau_hinh_cua_so_mac_dinh_la_50000()
    {
        $this->assertSame(50000, (int) config('order_check.scan_id_window'));
    }
}
