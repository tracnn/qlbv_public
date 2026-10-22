<?php

namespace Tests\Unit\SinhXml3176;

use App\Console\Commands\SinhXml3176Quet;
use App\Models\SinhXml3176\LanSinh;
use App\Services\SinhXml3176\Bang\DangKyBang;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\KiemDieuKien;
use App\Services\SinhXml3176\LuotQuet;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use App\Services\SinhXml3176\TheoDoiSinh;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\Support\HoSoHisSinhXml3176;
use Tests\TestCase;

class SinhXml3176QuetTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;
    use HoSoHisSinhXml3176;

    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->dungBangHis();
        $this->themDanhMucLuot();
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_lenh_' . uniqid();
        Cache::forget(SinhXml3176Quet::KHOA_LUOT);
    }

    protected function tearDown()
    {
        Cache::forget(SinhXml3176Quet::KHOA_LUOT);
        $this->xoaThuMuc($this->thuMuc);
        parent::tearDown();
    }

    private function cauHinh(array $khoi = [])
    {
        config([
            'organization.sinh_xml3176' => array_merge([
                'enabled' => true, 'ket_noi_his' => 'HISPro', 'thu_muc_thu' => $this->thuMuc,
            ], $khoi),
            'sinh_xml3176' => ['kich_thuoc_lo' => 50, 'chong_lan_phut' => 10, 'khoa_luot_phut' => 120],
        ]);
    }

    private function chay(array $thamSo = [])
    {
        $ma = Artisan::call('sinhxml3176:quet', $thamSo);

        return [$ma, Artisan::output()];
    }

    private function soTep()
    {
        return count(glob($this->thuMuc . DIRECTORY_SEPARATOR . '*.xml') ?: []);
    }

    public function test_module_tat_thi_thoat_em()
    {
        $this->cauHinh(['enabled' => false]);
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertContains('Module sinh XML3176 dang tat', $ra);
        $this->assertSame(0, LanSinh::count());
        $this->assertFalse(is_dir($this->thuMuc));
    }

    public function test_tu_choi_ghi_thu_muc_that_khi_thieu_bang()
    {
        $that = $this->thuMuc . DIRECTORY_SEPARATOR . 'that';
        $this->cauHinh(['ghi_thu_muc_that' => true, 'thu_muc' => $that, 'thu_muc_tt' => $that]);
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay();

        $this->assertSame(1, $ma);
        $this->assertContains('XML4', $ra);
        $this->assertContains('XML15', $ra);
        $this->assertSame(0, LanSinh::count());
        $this->assertFalse(is_dir($that));
        $this->assertFalse(Cache::has(SinhXml3176Quet::KHOA_LUOT));
    }

    public function test_quet_mot_vong_ghi_tep_va_in_tong_ket()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertSame(1, $this->soTep());
        $this->assertContains('chinh_thuc', $ra);
        $this->assertContains('da_ghi=1', $ra);
        $this->assertNotContains('thong_tuyen', $ra);
        $this->assertFalse(Cache::has(SinhXml3176Quet::KHOA_LUOT));
    }

    public function test_bat_thong_tuyen_thi_chay_ca_hai_ban()
    {
        $this->cauHinh(['thong_tuyen' => true]);
        $this->themHoSoLuot(1);
        $this->themHoSoLuot(2, ['fee_lock_time' => null]);

        list($ma, $ra) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertContains('thong_tuyen', $ra);
        $this->assertSame(2, $this->soTep());
        $this->assertCount(1, glob($this->thuMuc . DIRECTORY_SEPARATOR . '*_TT.xml'));
    }

    public function test_dang_co_luot_khac_thi_bo_qua()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);
        Cache::add(SinhXml3176Quet::KHOA_LUOT, true, 5);

        list($ma, $ra) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertContains('dang chay', $ra);
        $this->assertSame(0, LanSinh::count());
        // Khoa cua luot khac khong bi go
        $this->assertTrue(Cache::has(SinhXml3176Quet::KHOA_LUOT));
    }

    public function test_go_khoa()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);
        Cache::add(SinhXml3176Quet::KHOA_LUOT, true, 5);

        list($ma, $ra) = $this->chay(['--go-khoa' => true]);

        $this->assertSame(0, $ma);
        $this->assertFalse(Cache::has(SinhXml3176Quet::KHOA_LUOT));
        $this->assertSame(0, LanSinh::count());
    }

    public function test_lien_tuc_chay_so_vong()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay(['--lien-tuc' => true, '--so-vong' => 2, '--nghi' => 0]);

        $this->assertSame(0, $ma);
        $this->assertSame(1, $this->soTep());
        // Vong 2 doc lai trong cua so chong lan, bo qua
        $this->assertContains('bo_qua=1', $ra);
        $this->assertSame(1, LanSinh::count());
    }

    public function test_chay_bu_theo_ma_khong_dung_moc()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);

        list($ma, $ra) = $this->chay(['--ma-dieu-tri' => ['HS2', 'KHONGCO']]);

        $this->assertSame(0, $ma);
        $this->assertSame(['HS2'], LanSinh::pluck('treatment_code')->all());
        $this->assertContains('KHONGCO', $ra);
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(CauHinhSinhXml3176::CHINH_THUC));
    }

    public function test_chay_bu_theo_khoang_ngay()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261003110000]);

        list($ma, $ra) = $this->chay(['--tu-ngay' => '2026-10-03', '--den-ngay' => '2026-10-03']);

        $this->assertSame(0, $ma);
        $this->assertSame(['HS2'], LanSinh::pluck('treatment_code')->all());
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(CauHinhSinhXml3176::CHINH_THUC));
    }

    public function test_khoang_ngay_sai_dinh_dang_thi_loi()
    {
        $this->cauHinh();

        list($ma, $ra) = $this->chay(['--tu-ngay' => '03/10/2026']);

        $this->assertSame(1, $ma);
        $this->assertContains('--tu-ngay', $ra);
    }

    public static $khoaMoiVong = [];

    /** Goi tu luot gia: ghi nhan khoa co ton tai khong, roi go khoa nhu khi het han khoa_luot_phut. */
    public static function ghiNhanKhoa()
    {
        self::$khoaMoiVong[] = Cache::has(SinhXml3176Quet::KHOA_LUOT);
        Cache::forget(SinhXml3176Quet::KHOA_LUOT);
    }

    public function test_lien_tuc_lam_moi_khoa_moi_vong()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1);

        $lenh = new class extends SinhXml3176Quet {
            protected function taoLuot(CauHinhSinhXml3176 $cauHinh, DangKyBang $dangKy)
            {
                $ungVien = new HisUngVien($cauHinh);

                return new class($cauHinh, $ungVien, new HisNapHoSo($cauHinh, $ungVien), new TheoDoiSinh(), $dangKy, new KiemDieuKien()) extends LuotQuet {
                    public function chay($loaiBan)
                    {
                        SinhXml3176QuetTest::ghiNhanKhoa();

                        return parent::chay($loaiBan);
                    }
                };
            }
        };
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($lenh);
        self::$khoaMoiVong = [];

        list($ma, $ra) = $this->chay(['--lien-tuc' => true, '--so-vong' => 2, '--nghi' => 0]);

        $this->assertSame(0, $ma);
        // Vong 1 co khoa (roi bi go nhu het han); vong 2 khoa da duoc dat lai
        $this->assertSame([true, true], self::$khoaMoiVong);
        $this->assertFalse(Cache::has(SinhXml3176Quet::KHOA_LUOT));
    }

    public function test_chay_bu_hai_loai_ban_chi_canh_bao_ma_khong_co_o_loai_nao()
    {
        $this->cauHinh(['thong_tuyen' => true]);
        $this->themHoSoLuot(1);
        $this->themHoSoLuot(2, ['fee_lock_time' => null]);

        list($ma, $ra) = $this->chay(['--ma-dieu-tri' => ['HS1', 'HS2', 'KHONGCO']]);

        $this->assertSame(0, $ma);
        $this->assertSame(2, LanSinh::count());
        $this->assertContains('KHONGCO', $ra);
        $this->assertNotContains('HS1', $ra);
        $this->assertNotContains('HS2', $ra);
    }
}
