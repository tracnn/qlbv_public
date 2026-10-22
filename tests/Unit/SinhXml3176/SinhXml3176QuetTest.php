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

    const MOC_GOC = 20260101000000;

    private $goc;
    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->dungBangHis();
        $this->themDanhMucLuot();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_lenh_' . uniqid();
        mkdir($this->goc, 0775, true);
        $this->thuMuc = $this->goc . DIRECTORY_SEPARATOR . 'thu';
        Cache::forget(SinhXml3176Quet::KHOA_LUOT);
        (new TheoDoiSinh())->datMoc(CauHinhSinhXml3176::CHINH_THUC, self::MOC_GOC);
        (new TheoDoiSinh())->datMoc(CauHinhSinhXml3176::THONG_TUYEN, self::MOC_GOC);
        $this->memoryLimitCu = ini_get('memory_limit');
    }

    private $memoryLimitCu;

    protected function tearDown()
    {
        ini_set('memory_limit', $this->memoryLimitCu);
        Cache::forget(SinhXml3176Quet::KHOA_LUOT);
        $this->xoaThuMuc($this->goc);
        parent::tearDown();
    }

    private function cauHinh(array $khoi = [], array $kyThuat = [])
    {
        config([
            'organization.sinh_xml3176' => array_merge([
                'enabled' => true, 'ket_noi_his' => 'HISPro', 'thu_muc_thu' => $this->thuMuc,
            ], $khoi),
            'sinh_xml3176' => array_merge(['kich_thuoc_lo' => 50, 'chong_lan_phut' => 10, 'khoa_luot_phut' => 120], $kyThuat),
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
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(CauHinhSinhXml3176::CHINH_THUC));
    }

    public function test_chay_bu_theo_khoang_ngay()
    {
        $this->cauHinh();
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261003110000]);

        list($ma, $ra) = $this->chay(['--tu-ngay' => '2026-10-03', '--den-ngay' => '2026-10-03']);

        $this->assertSame(0, $ma);
        $this->assertSame(['HS2'], LanSinh::pluck('treatment_code')->all());
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(CauHinhSinhXml3176::CHINH_THUC));
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

    public static $daNgu = [];

    private function dangKyLenhNguGia()
    {
        $lenh = new class extends SinhXml3176Quet {
            protected function ngu($giay)
            {
                SinhXml3176QuetTest::$daNgu[] = $giay;
            }
        };
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($lenh);
        self::$daNgu = [];
    }

    public function test_module_tat_lien_tuc_thi_nghi_truoc_khi_thoat()
    {
        $this->cauHinh(['enabled' => false], ['nghi_giay' => 45]);
        $this->dangKyLenhNguGia();

        list($ma, $ra) = $this->chay(['--lien-tuc' => true]);

        $this->assertSame(0, $ma);
        $this->assertContains('dang tat', $ra);
        $this->assertSame([45], self::$daNgu);

        // --nghi ghi de; khong --lien-tuc thi khong nghi
        self::$daNgu = [];
        $this->chay(['--lien-tuc' => true, '--nghi' => 7]);
        $this->assertSame([7], self::$daNgu);
        self::$daNgu = [];
        $this->chay();
        $this->assertSame([], self::$daNgu);
    }

    public function test_lien_tuc_nghi_giua_cac_vong_qua_ngu()
    {
        $this->cauHinh();
        $this->dangKyLenhNguGia();

        list($ma, $ra) = $this->chay(['--lien-tuc' => true, '--so-vong' => 3, '--nghi' => 5]);

        $this->assertSame(0, $ma);
        $this->assertSame([5, 5], self::$daNgu);
    }

    public function test_dat_memory_limit_tu_config()
    {
        ini_set('memory_limit', '3G');
        $this->cauHinh(['enabled' => false], ['memory_limit' => '4G']);

        $this->chay();

        $this->assertSame('4G', ini_get('memory_limit'));
    }

    public function test_memory_limit_hien_tai_cao_hon_thi_giu_nguyen()
    {
        ini_set('memory_limit', '4G');
        $this->cauHinh(['enabled' => false], ['memory_limit' => '777M']);

        list($ma) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertSame('4G', ini_get('memory_limit'));
    }

    public function test_memory_limit_hien_tai_khong_gioi_han_thi_giu_nguyen()
    {
        ini_set('memory_limit', '-1');
        $this->cauHinh(['enabled' => false], ['memory_limit' => '777M']);

        list($ma) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertSame('-1', ini_get('memory_limit'));
    }

    public function test_memory_limit_cau_hinh_khong_hop_le_thi_giu_nguyen_khong_loi()
    {
        ini_set('memory_limit', '3G');
        $this->cauHinh(['enabled' => false], ['memory_limit' => 'abc']);

        list($ma) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertSame('3G', ini_get('memory_limit'));
    }

    public function test_memory_limit_ra_byte()
    {
        $this->assertSame(-1, CauHinhSinhXml3176::memoryLimitRaByte('-1'));
        $this->assertSame(1048576, CauHinhSinhXml3176::memoryLimitRaByte('1M'));
        $this->assertSame(1073741824, CauHinhSinhXml3176::memoryLimitRaByte(' 1g '));
        $this->assertSame(2048 * 1024, CauHinhSinhXml3176::memoryLimitRaByte('2048K'));
        $this->assertSame(12345, CauHinhSinhXml3176::memoryLimitRaByte('12345'));
        $this->assertNull(CauHinhSinhXml3176::memoryLimitRaByte('abc'));
        $this->assertNull(CauHinhSinhXml3176::memoryLimitRaByte(''));
    }

    public function test_thu_muc_thu_nam_trong_thu_muc_quet_thi_loi_cau_hinh()
    {
        $quet = $this->goc . DIRECTORY_SEPARATOR . 'Quet';
        // Khac hoa thuong + dau phan cach van bi bat
        $this->cauHinh(['thu_muc' => $quet, 'thu_muc_tt' => $quet . 'TT',
            'thu_muc_thu' => str_replace('\\', '/', strtolower($quet)) . '/thu/']);
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay();

        $this->assertSame(1, $ma);
        $this->assertContains('thu_muc_thu', $ra);
        $this->assertContains('nam trong thu_muc', $ra);
        $this->assertSame(0, LanSinh::count());
        $this->assertFalse(is_dir($quet));
        $this->assertFalse(Cache::has(SinhXml3176Quet::KHOA_LUOT));
    }

    public function test_ket_noi_his_rong_thi_loi_cau_hinh()
    {
        foreach ([null, '', '  '] as $gt) {
            $this->cauHinh(['ket_noi_his' => $gt]);

            list($ma, $ra) = $this->chay();

            $this->assertSame(1, $ma, var_export($gt, true));
            $this->assertContains('ket_noi_his', $ra);
        }
        $this->assertSame(0, LanSinh::count());
    }

    public function test_luot_dau_chua_co_moc_in_khoi_tao()
    {
        \App\Models\SinhXml3176\Moc::query()->delete();
        $this->cauHinh();
        $this->themHoSoLuot(1);

        list($ma, $ra) = $this->chay();

        $this->assertSame(0, $ma);
        $this->assertContains('da khoi tao moc', $ra);
        $this->assertSame(0, $this->soTep());
        $this->assertSame(0, LanSinh::count());
        $this->assertTrue((new TheoDoiSinh())->coMoc(CauHinhSinhXml3176::CHINH_THUC));
    }
}
