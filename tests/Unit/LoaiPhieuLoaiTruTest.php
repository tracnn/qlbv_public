<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Bon loai phieu duoc loai tru khoi order-check tai NGUON, tuc khong quy tac nao nhin thay.
 *
 * Do truoc khi lam, 7 ngay that: Suat an 89.931 phieu ma chua tung sinh mot vi pham nao,
 * Khac 13.787, Don mau 1.824, Ngoai KCB 0. Tong 105.542/917.663 y lenh (11,5%) khong con
 * phai quet.
 *
 * Loc tai nguon chu khong tai tung quy tac: dat o mot cho thi khong the sot quy tac nao,
 * va quy tac moi them sau nay cung tu dong duoc loai tru.
 */
class LoaiPhieuLoaiTruTest extends TestCase
{
    use LocComment;

    /** Ma nguon HisOrderSource da bo comment */
    protected function ma()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Services/OrderCheck/HisOrderSource.php'));
    }

    /** @test */
    public function cau_hinh_co_du_bon_loai_phieu()
    {
        // 11 Khac, 16 Don mau, 17 Suat an, 18 Ngoai kham chua benh — theo
        // his_service_req_type.
        $csv = (string) config('order_check.exclude_service_req_type_ids');
        $ds = array_map('intval', array_filter(explode(',', $csv), 'strlen'));

        sort($ds);

        $this->assertSame([11, 16, 17, 18], $ds);
    }

    /** @test */
    public function truy_van_co_ap_dieu_kien_loai_tru()
    {
        $ma = $this->ma();

        $this->assertContains('excludeServiceReqTypeIds', $ma,
            'HisOrderSource khong con doc cau hinh loai tru loai phieu');
        $this->assertContains('sr.service_req_type_id', $ma,
            'Khong thay dieu kien loc theo loai phieu');
    }

    /**
     * Cau hinh RONG phai nghia la KHONG loc, khong phai loc bang mang rong.
     *
     * whereNotIn voi mang rong tren Oracle sinh menh de vo nghia; te hon, neu ai do viet
     * nham thanh whereIn thi cau hinh rong se loai HET moi y lenh va order-check im lang
     * khong bao gi ca.
     */
    /** @test */
    public function cau_hinh_rong_thi_khong_loc()
    {
        $cu = config('order_check.exclude_service_req_type_ids');

        try {
            config(['order_check.exclude_service_req_type_ids' => '']);

            $src = new \App\Services\OrderCheck\HisOrderSource();
            $r = new \ReflectionProperty($src, 'excludeServiceReqTypeIds');
            $r->setAccessible(true);

            $this->assertSame([], $r->getValue($src));
        } finally {
            config(['order_check.exclude_service_req_type_ids' => $cu]);
        }
    }
}
