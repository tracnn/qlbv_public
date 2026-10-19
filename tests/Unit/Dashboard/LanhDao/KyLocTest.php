<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Services\Dashboard\LanhDao\KyLoc;

class KyLocTest extends TestCase
{
    /** @test */
    public function tu_request_doi_ngay_sang_ymdhis()
    {
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'co_so' => 'nb']);

        $this->assertSame('20261001000000', $ky->from);
        $this->assertSame('20261019235959', $ky->to);
        $this->assertSame('nb', $ky->coSo);
        $this->assertSame([81], $ky->branchIds());
    }

    /** @test */
    public function co_so_mac_dinh_2cs_gom_hai_branch()
    {
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19']);

        $this->assertSame('2cs', $ky->coSo);
        $this->assertSame([1, 81], $ky->branchIds());
        $this->assertSame([1], KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'co_so' => 'hn'])->branchIds());
    }

    /** @test */
    public function qua_92_ngay_nem_validation()
    {
        $this->expectException(ValidationException::class);
        KyLoc::tuRequest(['from' => '2026-01-01', 'to' => '2026-06-30']);
    }

    /** @test */
    public function dung_92_ngay_van_hop_le()
    {
        $ky = KyLoc::tuRequest(['from' => '2026-07-01', 'to' => '2026-09-30']);
        $this->assertSame('20260930235959', $ky->to);
        $this->expectException(ValidationException::class);
        KyLoc::tuRequest(['from' => '2026-07-01', 'to' => '2026-10-01']);
    }

    /** @test */
    public function to_truoc_from_nem_validation()
    {
        $this->expectException(ValidationException::class);
        KyLoc::tuRequest(['from' => '2026-10-19', 'to' => '2026-10-18']);
    }

    /** @test */
    public function co_so_la_nem_validation()
    {
        $this->expectException(ValidationException::class);
        KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'co_so' => 'xyz']);
    }

    /** @test */
    public function sai_dinh_dang_ngay_nem_validation()
    {
        $this->expectException(ValidationException::class);
        KyLoc::tuRequest(['from' => '19/10/2026', 'to' => '2026-10-19']);
    }

    /** @test */
    public function den_thuc_te_cat_tai_now()
    {
        $now = Carbon::create(2026, 10, 19, 10, 0, 0);
        $ky = KyLoc::tuRequest(['from' => '2026-10-19', 'to' => '2026-10-19'], $now);

        $this->assertSame('20261019100000', $ky->denThucTe());
        $this->assertEquals(10 / 24, $ky->soNgayThucTe(), '', 0.001);
    }

    /** @test */
    public function den_thuc_te_giu_to_khi_ky_da_qua()
    {
        $now = Carbon::create(2026, 10, 19, 10, 0, 0);
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-10'], $now);

        $this->assertSame('20261010235959', $ky->denThucTe());
    }

    /** @test */
    public function da_ket_thuc()
    {
        $now = Carbon::create(2026, 10, 19, 10, 0, 0);

        $this->assertTrue(KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-18'], $now)->daKetThuc());
        $this->assertFalse(KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19'], $now)->daKetThuc());
    }

    /** @test */
    public function khoa_cache_ghep_phan_tu_khoang_va_co_so()
    {
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'co_so' => 'hn']);

        $this->assertSame('lanh_dao:the:20261001000000:20261019235959:hn', $ky->khoaCache('the'));
    }
}
