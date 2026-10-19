<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01Nguon;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01NguonTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
    }

    private function dong(array $ghiDe = array())
    {
        DB::table('xml3176_xml1s')->insert(array_merge(array(
            'ma_lk' => 'LK', 'ma_the_bhyt' => 'DN4010000000001', 'ma_cskcb' => '01929',
            'nam_qt' => '2026', 'thang_qt' => '09', 'ma_loai_kcb' => '01',
            'ngay_ra' => '202609010800',
            't_tongchi_bv' => 0, 't_tongchi_bh' => 0, 't_bhtt' => 0,
            't_bncct' => 0, 't_bntt' => 0, 't_nguonkhac' => 0,
        ), $ghiDe));
    }

    private function ky()
    {
        return Bh01Ky::tu(2026, 9);
    }

    /** @test */
    public function chi_lay_ho_so_bhyt_dung_co_so_dung_ky()
    {
        $this->dong(array('ma_lk' => 'A'));
        $this->dong(array('ma_lk' => 'B'));
        $this->dong(array('ma_lk' => 'C', 'ma_the_bhyt' => ''));
        $this->dong(array('ma_lk' => 'D', 'ma_cskcb' => '37470'));
        $this->dong(array('ma_lk' => 'E', 'thang_qt' => '10'));

        $this->assertSame(2, Bh01Nguon::truyVan('01929', $this->ky())->count());
    }

    /** @test */
    public function thu_tu_theo_muc_roi_ngay_ra_roi_ma_lk()
    {
        $this->dong(array('ma_lk' => 'IV', 'ma_loai_kcb' => '03', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-B', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'III', 'ma_loai_kcb' => '04', 'ngay_ra' => '202609010100'));
        $this->dong(array('ma_lk' => 'I-A', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609050800'));
        $this->dong(array('ma_lk' => 'I-0', 'ma_loai_kcb' => '01', 'ngay_ra' => '202609020800'));
        $this->dong(array('ma_lk' => 'LA', 'ma_loai_kcb' => '99', 'ngay_ra' => '202609010100'));

        $this->assertSame(
            array('I-0', 'I-A', 'I-B', 'III', 'IV', 'LA'),
            Bh01Nguon::truyVan('01929', $this->ky())->pluck('ma_lk')->all()
        );
    }

    /** @test */
    public function tong_hop_dung_so_lieu()
    {
        $this->dong(array('ma_loai_kcb' => '01', 't_bhtt' => 100.1, 't_tongchi_bv' => 10));
        $this->dong(array('ma_loai_kcb' => '06', 't_bhtt' => 50));
        $this->dong(array('ma_loai_kcb' => '03', 't_bhtt' => 25));
        $this->dong(array('ma_loai_kcb' => '99', 't_bhtt' => 5));
        $this->dong(array('ma_loai_kcb' => '01', 't_bhtt' => 999, 'ma_the_bhyt' => ''));

        $th = Bh01Nguon::tongHop('01929', $this->ky());

        $this->assertSame(4, $th['so_ho_so']);
        $this->assertSame(array(1 => 2, 2 => 0, 3 => 0, 4 => 1, 'khac' => 1), $th['theo_muc']);
        $this->assertSame(180.1, $th['t_bhtt']);
        $this->assertSame(10.0, $th['t_tongchi_bv']);
    }

    /** @test */
    public function tong_hop_khong_co_dong_tra_ve_khong()
    {
        $th = Bh01Nguon::tongHop('01929', $this->ky());

        $this->assertSame(0, $th['so_ho_so']);
        $this->assertSame(0.0, $th['t_bhtt']);
        $this->assertSame(array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 'khac' => 0), $th['theo_muc']);
    }

    /** @test */
    public function thang_mot_chu_so_duoc_dem_so_khong()
    {
        $this->assertSame('09', Bh01Ky::tu(2026, 9)['thang']);
        $this->assertSame('202609', Bh01Ky::tu('2026', '9')['ky_qt']);
    }

    /** @test */
    public function ky_truoc_04_2026_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01Ky::tu(2026, 3);
    }

    /** @test */
    public function thang_ngoai_1_den_12_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);
        Bh01Ky::tu(2026, 13);
    }
}
