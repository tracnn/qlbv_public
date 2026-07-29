<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan viec ai do sau nay go join danh muc thuoc hoac quay lai gan thang
 * hein_service_bhyt_code - hai viec do deu lam quy tac A_BHYT_CODE_MISSING bao sai lai
 * toan bo dong thuoc, ma khong test nao khac bat duoc.
 */
class HisOrderSourceMaBhytTest extends TestCase
{
    use LocComment;

    protected function ma()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Services/OrderCheck/HisOrderSource.php'));
    }

    /** @test */
    public function co_join_toi_danh_muc_thuoc()
    {
        $ma = $this->ma();

        $this->assertContains('his_medicine', $ma, 'Mat join his_medicine');
        $this->assertContains('his_medicine_type', $ma, 'Mat join his_medicine_type');
    }

    /** @test */
    public function co_chon_cot_ma_hoat_chat()
    {
        $this->assertContains('active_ingr_bhyt_code', $this->ma(),
            'Truy van khong con chon cot ma hoat chat BHYT');
    }

    /** @test */
    public function dung_ma_bhyt_dong_de_chon_ma()
    {
        $this->assertContains('MaBhytDong', $this->ma(),
            'Khong con dung MaBhytDong - co the da quay lai gan thang hein_service_bhyt_code');
    }
}
