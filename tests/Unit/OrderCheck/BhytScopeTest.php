<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\OrderService;

class BhytScopeTest extends TestCase
{
    private function dv($patientTypeId)
    {
        $s = new OrderService();
        $s->sereServId = rand(1, 99999);
        $s->serviceCode = 'DV1';
        $s->patientTypeId = $patientTypeId;

        return $s;
    }

    private function datCauHinh($csv)
    {
        config(['order_check.bhyt_patient_type_ids' => $csv]);
    }

    /** @test */
    public function doc_danh_sach_doi_tuong_tu_cau_hinh()
    {
        $this->datCauHinh('1');
        $this->assertEquals([1], BhytScope::dsDoiTuong());

        $this->datCauHinh('1,5');
        $this->assertEquals([1, 5], BhytScope::dsDoiTuong());
    }

    /** @test */
    public function cau_hinh_rong_nghia_la_khong_loc()
    {
        // Duong lui: dat rong thi hanh vi quay ve dung nhu truoc dot nay.
        $this->datCauHinh('');

        $this->assertEquals([], BhytScope::dsDoiTuong());
        $this->assertTrue(BhytScope::laDongBhyt(42), 'Khong loc thi moi dong deu duoc xet');
        $this->assertTrue(BhytScope::laDongBhyt(null));

        $ds = [$this->dv(42), $this->dv(1)];
        $this->assertCount(2, BhytScope::locDongBhyt($ds));
        $this->assertTrue(BhytScope::coDongBhyt($ds));
    }

    /** @test */
    public function loc_dung_dong_bhyt_va_bo_dong_vien_phi()
    {
        // 43.264 dong Vien phi (02) nam trong ho so BHYT trong 7 ngay - neu khong loc o
        // muc DONG thi chung bi doi chieu danh muc BHXH va bat loi oan.
        $this->datCauHinh('1');

        $this->assertTrue(BhytScope::laDongBhyt(1));
        $this->assertFalse(BhytScope::laDongBhyt(42), 'Vien phi khong duoc xet');
        $this->assertFalse(BhytScope::laDongBhyt(null));

        $ds = [$this->dv(1), $this->dv(42), $this->dv(1)];
        $this->assertCount(2, BhytScope::locDongBhyt($ds));
    }

    /** @test */
    public function co_dong_bhyt_dung_cho_tang_loc_tho()
    {
        $this->datCauHinh('1');

        $this->assertTrue(BhytScope::coDongBhyt([$this->dv(42), $this->dv(1)]));
        $this->assertFalse(BhytScope::coDongBhyt([$this->dv(42), $this->dv(43)]));
        $this->assertFalse(BhytScope::coDongBhyt([]), 'Phieu khong co dong nao thi khong co dong BHYT');
    }

    /** @test */
    public function loc_dong_giu_nguyen_thu_tu_va_danh_so_lai()
    {
        $this->datCauHinh('1');

        $a = $this->dv(1);
        $b = $this->dv(42);
        $c = $this->dv(1);

        $kq = BhytScope::locDongBhyt([$a, $b, $c]);

        $this->assertSame([$a, $c], $kq);
        $this->assertEquals([0, 1], array_keys($kq));
    }
}
