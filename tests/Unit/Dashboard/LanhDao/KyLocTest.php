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
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'ma_cskcb' => '37470']);

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
        $this->assertSame([1], KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'ma_cskcb' => '01929'])->branchIds());
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
    }

    /** @test */
    public function dung_93_ngay_nem_validation()
    {
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
    public function ma_cskcb_la_hoac_rong_coi_nhu_tat_ca_co_so()
    {
        // Giống LocCoSo::maHopLe: mã không thuộc danh sách = không lọc, không ném lỗi
        foreach (['xyz', '', '   ', '99999'] as $ma) {
            $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'ma_cskcb' => $ma]);
            $this->assertSame('2cs', $ky->coSo, "ma_cskcb='{$ma}'");
        }
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'ma_cskcb' => ' 01929 ']);
        $this->assertSame('hn', $ky->coSo);
    }

    /** @test */
    public function lua_chon_co_so_chi_gom_ma_da_cau_hinh_nhan_lay_tu_his()
    {
        $his = [
            '01929' => '01929 — BỆNH VIỆN BẠCH MAI',
            '01999' => '01999 — Phòng Y tế cơ quan',
            '37470' => '37470 — BỆNH VIỆN BẠCH MAI CƠ SỞ NINH BÌNH',
        ];

        $this->assertSame([
            '01929' => '01929 — BỆNH VIỆN BẠCH MAI',
            '37470' => '37470 — BỆNH VIỆN BẠCH MAI CƠ SỞ NINH BÌNH',
        ], KyLoc::luaChonCoSo($his));
    }

    /** @test */
    public function lua_chon_co_so_his_hong_van_co_nhan_tu_cau_hinh()
    {
        // DanhSachCoSo trả [] khi HIS lỗi — ô chọn vẫn phải có đủ cơ sở của dashboard
        $this->assertSame([
            '01929' => '01929 — HN',
            '37470' => '37470 — NB',
        ], KyLoc::luaChonCoSo([]));
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
        $ky = KyLoc::tuRequest(['from' => '2026-10-01', 'to' => '2026-10-19', 'ma_cskcb' => '01929']);

        $this->assertSame('lanh_dao:the:20261001000000:20261019235959:hn', $ky->khoaCache('the'));
    }
}
