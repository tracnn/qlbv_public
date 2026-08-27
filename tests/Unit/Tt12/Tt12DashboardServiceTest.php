<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Cache;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Dashboard\Tt12DashboardService;

/**
 * Man dashboard do phu danh muc TT12.
 *
 * DanhSachCoSo doc bang his_branch tren Oracle HIS qua Cache::remember. Nap san cache trong
 * setUp() de test khong phu thuoc ket noi HIS - driver cache khi test la 'array' nen khong
 * ro ri sang test khac. Day la khuon da dung o Tt12ControllerTest.
 */
class Tt12DashboardServiceTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Cache::put(DanhSachCoSo::KHOA_CACHE, array(
            '01929' => '01929 - Bach Mai',
            '37470' => '37470 - Ninh Binh',
        ), 60);
    }

    private function tao($ma, array $ghiDe = array())
    {
        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 10,
            'id_danh_sach' => 'Id-' . $ma,
            'checked_at' => '2026-08-26 10:00:00', 'so_loi' => 0,
        ), $ghiDe));
    }

    /** @test */
    public function chua_co_ho_so_nao_thi_luoi_van_du_sau_mau_nhan_hai_co_so()
    {
        // Luoi phai DAY DU ngay ca khi rong. Chi ve o co du lieu thi co so chua khai bien
        // mat - dung cai ma man hinh nay sinh ra de phat hien.
        $kq = (new Tt12DashboardService())->doPhu();

        $this->assertCount(6, $kq['luoi'], 'phai du sau mau');

        foreach ($kq['luoi'] as $maMau => $theoCoSo) {
            $this->assertCount(2, $theoCoSo, $maMau . ': phai du hai co so');

            foreach ($theoCoSo as $maCs => $o) {
                $this->assertFalse($o['da_tiep_nhan'], $maMau . '/' . $maCs);
                $this->assertNull($o['tiep_nhan_luc']);
                $this->assertSame(0, $o['so_dong']);
            }
        }
    }
}
