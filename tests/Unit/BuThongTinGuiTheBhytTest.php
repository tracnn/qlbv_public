<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class BuThongTinGuiTheBhytTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
        $this->themHoSo(); // 01013250800123, DN4010112345678, 19790220, DKBD 01005

        DB::table('check_hein_cards')->insert([
            ['ma_lk' => '01013250800123', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'KHONG-CO-TREN-HIS', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'HOP-LE', 'ma_tracuu' => '000', 'ma_kiemtra' => '00',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
        ]);
    }

    private function dong($maLk)
    {
        return DB::table('check_hein_cards')->where('ma_lk', $maLk)->first();
    }

    /** @test */
    public function mac_dinh_chi_dem_khong_ghi()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui');

        $this->assertContains('Se bu: 1 | Khong thay tren HIS: 1 | HIS khong co so the: 0', Artisan::output());
        $this->assertNull($this->dong('01013250800123')->ma_the_gui);
    }

    /** @test */
    public function co_ghi_thi_bu_dung_va_khong_cham_updated_at()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);

        $r = $this->dong('01013250800123');
        $this->assertSame('DN4010112345678', $r->ma_the_gui);
        $this->assertSame('Nguyễn Văn A', $r->ho_ten_gui);
        $this->assertSame(dob('19790220'), $r->ngay_sinh_gui);
        $this->assertSame('01005', $r->ma_dkbd_gui);
        $this->assertSame('2026-08-01 08:00:00', $r->updated_at);
        $this->assertContains('Da bu: 1 | Khong thay tren HIS: 1 | HIS khong co so the: 0', Artisan::output());
    }

    /** @test */
    public function his_khong_co_so_the_thi_dem_rieng_va_khong_ghi_ma_the_gui_null()
    {
        DB::table('check_hein_cards')->insert([
            'ma_lk' => 'KHONG-CO-SO-THE', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
            'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00',
        ]);
        $this->themHoSo(['treatment_code' => 'KHONG-CO-SO-THE', 'tdl_hein_card_number' => null]);

        Artisan::call('the-bhyt:bu-thong-tin-gui');
        $this->assertContains('Se bu: 1 | Khong thay tren HIS: 1 | HIS khong co so the: 1', Artisan::output());

        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);
        $this->assertNull($this->dong('KHONG-CO-SO-THE')->ma_the_gui);
    }

    /** @test */
    public function mac_dinh_bo_qua_dong_hop_le_tat_ca_thi_xet_ca()
    {
        $this->themHoSo(['treatment_code' => 'HOP-LE']);

        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);
        $this->assertNull($this->dong('HOP-LE')->ma_the_gui);

        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true, '--tat-ca' => true]);
        $this->assertSame('DN4010112345678', $this->dong('HOP-LE')->ma_the_gui);
    }

    /** @test */
    public function chay_lai_khong_xet_dong_da_bu()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);
        Artisan::call('the-bhyt:bu-thong-tin-gui');

        $this->assertContains('Se bu: 0 | Khong thay tren HIS: 1 | HIS khong co so the: 0', Artisan::output());
    }
}
