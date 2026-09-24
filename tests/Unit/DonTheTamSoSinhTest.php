<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * 1.149 dong loi cu cua the tam (TE1 + DKBD XX000) cua benh nhan da ra vien - lenh quet
 * hang ngay chi quet BN dang nam nen job khong bao gio xoa duoc chung.
 */
class DonTheTamSoSinhTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();

        // The tam, lay tu HIS (chua co _gui)
        $this->themHoSo(['treatment_code' => 'TAM-HIS', 'tdl_hein_card_number' => 'TE1010000012345', 'tdl_hein_medi_org_code' => '01000']);
        // The thuong co loi that
        $this->themHoSo(['treatment_code' => 'THUONG']);

        DB::table('check_hein_cards')->insert([
            ['ma_lk' => 'TAM-HIS', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'ma_the_gui' => null, 'ma_dkbd_gui' => null,
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            // The tam, da co _gui, khong co tren HIS
            ['ma_lk' => 'TAM-GUI', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'ma_the_gui' => 'TE1373700012345', 'ma_dkbd_gui' => '37000',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'THUONG', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'ma_the_gui' => null, 'ma_dkbd_gui' => null,
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            // The tam nhung ket qua HOP LE: khong dung toi
            ['ma_lk' => 'TAM-HOP-LE', 'ma_tracuu' => '000', 'ma_kiemtra' => '00',
             'ma_the_gui' => 'TE1010000054321', 'ma_dkbd_gui' => '01000',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'KHONG-THAY', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'ma_the_gui' => null, 'ma_dkbd_gui' => null,
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
        ]);
    }

    private function con($maLk)
    {
        return DB::table('check_hein_cards')->where('ma_lk', $maLk)->exists();
    }

    /** @test */
    public function mac_dinh_chi_dem()
    {
        Artisan::call('the-bhyt:don-the-tam');

        $this->assertContains('Xet: 4 | The tam: 2 | Khong thay tren HIS: 1', Artisan::output());
        $this->assertTrue($this->con('TAM-HIS'));
        $this->assertTrue($this->con('TAM-GUI'));
    }

    /** @test */
    public function co_ghi_chi_xoa_the_tam_loi()
    {
        Artisan::call('the-bhyt:don-the-tam', ['--ghi' => true]);

        $this->assertFalse($this->con('TAM-HIS'));
        $this->assertFalse($this->con('TAM-GUI'));
        $this->assertTrue($this->con('THUONG'));
        $this->assertTrue($this->con('TAM-HOP-LE'));
        $this->assertTrue($this->con('KHONG-THAY'));
        $this->assertContains('Da xoa: 2', Artisan::output());
    }
}
