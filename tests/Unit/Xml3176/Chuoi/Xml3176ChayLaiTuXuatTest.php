<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\ExportXml3176Job;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Cuu ho so da kiem xong ma chua xuat - 5.043 ho so ngay 29/09/2026 het luot cho.
 */
class Xml3176ChayLaiTuXuatTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);

        // Da kiem, chua xuat, sach -> chon
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'SACH', 'macskcb' => '01929', 'chain_token' => 'MA_CU',
            'checked_at' => '2026-09-29 10:00:00', 'export_error' => 'Không xuất: chờ 150 giây mà bước kiểm lỗi chưa xong.',
        ]);
        // Da kiem, chua xuat, co loi nghiem trong -> chon (buoc xuat se chan lai)
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LOI', 'macskcb' => '01929', 'chain_token' => 'MA_CU',
            'checked_at' => '2026-09-29 10:00:00',
        ]);
        // Da xuat -> khong chon
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'DA_XUAT', 'macskcb' => '01929', 'chain_token' => 'MA_CU',
            'checked_at' => '2026-09-29 10:00:00', 'exported_at' => '2026-09-29 10:01:00',
        ]);
        // Chua kiem xong (dang kiem do, hoac nap truoc 28/09) -> khong chon
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'CHUA_KIEM', 'macskcb' => '01929', 'chain_token' => 'MA_CU',
        ]);
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LOI', 'xml' => 'XML1', 'stt' => 1, 'error_code' => 'X', 'description' => 'x', 'critical_error' => 1,
        ]);

        Queue::fake();
    }

    private function maDaDay()
    {
        return Queue::pushed(ExportXml3176Job::class)->map(function ($job) {
            $p = new \ReflectionProperty($job, 'ma_lk');
            $p->setAccessible(true);
            return $p->getValue($job);
        })->sort()->values()->all();
    }

    /** @test */
    public function mac_dinh_chi_dem_khong_day_gi()
    {
        Artisan::call('xml3176:chay-lai-tu-xuat');
        $ra = Artisan::output();

        Queue::assertNothingPushed();
        $this->assertContains('2 hồ sơ', $ra);
        $this->assertContains('1 sạch', $ra);
        $this->assertContains('1 có lỗi nghiêm trọng', $ra);
        $this->assertContains('--thuc-hien', $ra);
        $this->assertSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'SACH')->value('chain_token'),
            'Chi dem thi khong duoc doi ma phien');
    }

    /** @test */
    public function thuc_hien_day_dung_tap_da_kiem_chua_xuat_va_sinh_ma_moi()
    {
        Artisan::call('xml3176:chay-lai-tu-xuat', ['--thuc-hien' => true]);

        $this->assertSame(['LOI', 'SACH'], $this->maDaDay());
        $this->assertNotSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'SACH')->value('chain_token'));
        $this->assertSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'DA_XUAT')->value('chain_token'));
    }

    /** @test */
    public function chi_dinh_ma_lk_bo_qua_tieu_chi_mac_dinh()
    {
        // Dung khi ky lai sau su co HSM: ho so da xuat nhung chua ky.
        Artisan::call('xml3176:chay-lai-tu-xuat', ['--ma-lk' => ['DA_XUAT'], '--thuc-hien' => true]);

        $this->assertSame(['DA_XUAT'], $this->maDaDay());
    }
}
