<?php

namespace Tests\Unit;

use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;

/**
 * $fillable tung ghi 'gt_the_tu_moi'/'gt_the_den_moi' trong khi cot that la 'gt_the_tumoi'/
 * 'gt_the_denmoi': updateOrCreate lang le bo hai truong, 0/46.396 dong co du lieu.
 */
class CheckHeinCardModelTest extends TestCase
{
    /** @test */
    public function fillable_nhan_dung_ten_cot_the_moi_va_cot_da_gui()
    {
        $m = (new check_hein_card)->fill([
            'gt_the_tumoi' => '01/10/2026', 'gt_the_denmoi' => '30/09/2027',
            'ma_the_gui' => 'DN4010112345678', 'ho_ten_gui' => 'Nguyễn Văn A',
            'ngay_sinh_gui' => '20/02/1979', 'ma_dkbd_gui' => '01005',
        ]);

        $this->assertSame('01/10/2026', $m->gt_the_tumoi);
        $this->assertSame('30/09/2027', $m->gt_the_denmoi);
        $this->assertSame('DN4010112345678', $m->ma_the_gui);
        $this->assertSame('01005', $m->ma_dkbd_gui);
    }

    /** @test */
    public function hien_thi_uu_tien_gia_tri_cong()
    {
        $m = new check_hein_card(['ma_the' => 'DN4010112345678', 'ma_the_gui' => 'KHAC']);

        $this->assertSame(['DN4010112345678', 'cong'], $m->hienThi('ma_the'));
    }

    /** @test */
    public function hien_thi_lay_gia_tri_da_gui_khi_cong_rong()
    {
        $m = new check_hein_card(['ho_ten' => '  ', 'ho_ten_gui' => 'Nguyễn Văn A']);

        $this->assertSame(['Nguyễn Văn A', 'gui'], $m->hienThi('ho_ten'));
    }

    /** @test */
    public function hien_thi_rong_khi_ca_hai_rong()
    {
        $this->assertSame(['', ''], (new check_hein_card)->hienThi('ngay_sinh'));
    }

    /** @test */
    public function truong_hien_la_ba_cot_quan_sat()
    {
        $this->assertSame(['ma_the', 'ho_ten', 'ngay_sinh'], check_hein_card::TRUONG_HIEN);
    }
}
