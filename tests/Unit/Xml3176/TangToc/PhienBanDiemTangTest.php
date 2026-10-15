<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Http\Controllers\Category\CategoryBHYTController;
use App\Jobs\CheckXml3176TypeJob;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\CatalogImportService;
use App\Services\Tt12\Tt12DongBoDanhMuc;
use App\Services\Xml3176ErrorService;
use App\Services\Xml3176\VatPhamKemDvktQuanTri;
use App\Services\Xml3176\Xml3176BoDem;
use App\Services\Xml3176\Xml3176PhienBanDanhMuc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangTt12Sqlite;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Task 8: moi duong ghi danh muc tang phien ban; job kiem doc phien ban luc bat dau.
 */
class PhienBanDiemTangTest extends TestCase
{
    use Xml3176RuleTestSupport, DungBangTt12Sqlite;

    const MIGRATION = '2026_10_15_130000_create_xml3176_phien_ban_danh_muc_table.php';

    private function boot(array $them = [])
    {
        $this->bootXml3176Sqlite(array_merge($them, [self::MIGRATION]));
    }

    private function phienBan()
    {
        return (int) DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->value('phien_ban');
    }

    /** @test */
    public function nhap_danh_muc_tang_ca_khi_nhap_loi()
    {
        $this->boot();

        try {
            app(CatalogImportService::class)->import(sys_get_temp_dir() . '/khong_co_tep_' . uniqid() . '.xlsx');
            $this->fail('Phai nem');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            // nhap loi giua chung: van phai tang (co the da ghi mot phan)
        }

        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function sua_muc_chan_ma_loi_tang()
    {
        $this->boot(['2026_01_09_161708_create_xml3176_error_catalogs_table.php']);
        $c = Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => 'X', 'error_name' => 'x',
            'description' => 'x', 'critical_error' => true, 'is_check' => true]);

        $res = $this->app->make(CategoryBHYTController::class)
            ->updateXml3176ErrorCatalog(Request::create('/x', 'POST', ['id' => $c->id, 'is_check' => 0]));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function xoa_danh_muc_tang()
    {
        $this->boot(['2024_07_04_072537_create_icd10_categories_table.php']);
        DB::table('icd10_categories')->insert(['icd_code' => 'A00', 'icd_name' => 'x']);

        $res = $this->app->make(CategoryBHYTController::class)
            ->xoaDanhMuc(Request::create('/x', 'POST', ['loai' => 'icd10', 'xac_nhan' => 'XOA']));

        $this->assertSame(1, $res->getData()->so_dong);
        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function quan_tri_vat_pham_kem_dvkt_tang()
    {
        $this->boot([
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
        ]);
        $m = Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền',
            'ma_dvkt' => '12.0448.1187', 'nguon' => 'HOC', 'hieu_luc' => true,
        ]);

        (new VatPhamKemDvktQuanTri())->datHieuLuc($m, false, 'sai', 1);

        $this->assertSame(1, $this->phienBan());
    }

    private function bootTt12()
    {
        $this->dungBangTt12();
        $this->dungBangDanhMucTt12();
        require_once database_path('migrations/' . self::MIGRATION);
        (new \CreateXml3176PhienBanDanhMucTable())->up();
    }

    private function hoSoTt12($maKetQua, $soDong)
    {
        $hoSo = Tt12HoSo::create([
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_00' . $soDong . $maKetQua,
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => $soDong, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
            'is_signed' => true, 'ma_ket_qua' => $maKetQua, 'ma_gd' => 'GD1',
        ]);
        for ($i = 0; $i < $soDong; $i++) {
            Tt12Dong::create(['ho_so_id' => $hoSo->id, 'stt' => $i + 1, 'du_lieu' => [
                'STT' => '1', 'MA_KHOA' => 'K0' . $i, 'TEN_KHOA' => 'Kham', 'BAN_KHAM' => '3',
                'GIUONG_PD' => '0', 'GIUONG_TK' => '0', 'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
                'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            ]]);
        }

        return $hoSo;
    }

    /** @test */
    public function dong_bo_tt12_tang_chi_khi_co_ghi()
    {
        $this->bootTt12();
        $sv = new Tt12DongBoDanhMuc();

        $this->assertSame(0, $sv->dongBo($this->hoSoTt12('200', 0)));
        $this->assertSame(0, $sv->dongBo($this->hoSoTt12('500', 1)));
        $this->assertSame(0, $this->phienBan(), 'khong ghi dong nao -> khong tang');

        $this->assertSame(2, $sv->dongBo($this->hoSoTt12('200', 2)));
        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function dong_bo_tt12_lo_sau_loi_van_tang_va_nem_lai()
    {
        $this->bootTt12();
        $hoSo = $this->hoSoTt12('200', 0);
        $dong = [];
        for ($i = 1; $i <= Tt12DongBoDanhMuc::CO_LO + 1; $i++) {
            $dong[] = ['ho_so_id' => $hoSo->id, 'stt' => $i, 'du_lieu' => json_encode([
                'STT' => (string) $i, 'MA_KHOA' => 'K' . $i, 'TEN_KHOA' => 'Kham', 'BAN_KHAM' => '3',
                'GIUONG_PD' => '0', 'GIUONG_TK' => '0', 'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
                'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            ])];
        }
        foreach (array_chunk($dong, 200) as $lo) {
            DB::table('tt12_dong')->insert($lo);
        }

        // Doc lo thu hai (offset CO_LO) that bai, sau khi lo dau da commit.
        DB::listen(function ($q) {
            if (strpos($q->sql, 'tt12_dong') !== false && strpos($q->sql, 'offset ' . Tt12DongBoDanhMuc::CO_LO) !== false) {
                throw new \RuntimeException('mat ket noi giua chung');
            }
        });

        try {
            (new Tt12DongBoDanhMuc())->dongBo($hoSo);
            $this->fail('Phai nem lai');
        } catch (\RuntimeException $e) {
            $this->assertSame('mat ket noi giua chung', $e->getMessage());
        }

        $this->assertSame(Tt12DongBoDanhMuc::CO_LO, DB::table('department_bed_catalogs')->count(), 'lo dau da ghi');
        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function nap_vat_pham_kem_dvkt_loi_giua_chung_van_tang()
    {
        $this->boot([
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
        ]);
        $soChen = 0;
        // Lenh chen thu hai nem (dong dau da ghi va da dem).
        DB::listen(function ($q) use (&$soChen) {
            if (stripos($q->sql, 'insert into "xml3176_vat_pham_kem_dvkt"') === 0 && ++$soChen === 2) {
                throw new \RuntimeException('loi dong 2');
            }
        });

        try {
            (new \App\Services\Xml3176\VatPhamKemDvktLearner())->nap([
                ['loai' => 'THUOC', 'ma_vat_pham' => '40.540', 'ma_dvkt' => '18.0150.0041'],
                ['loai' => 'THUOC', 'ma_vat_pham' => '40.541', 'ma_dvkt' => '18.0150.0041'],
            ]);
            $this->fail('Phai nem lai');
        } catch (\RuntimeException $e) {
            $this->assertSame('loi dong 2', $e->getMessage());
        }

        $this->assertSame(1, $this->phienBan());
    }

    /** @test */
    public function nap_vat_pham_kem_dvkt_khong_ghi_dong_nao_thi_khong_tang()
    {
        $this->boot([
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
        ]);

        $kq = (new \App\Services\Xml3176\VatPhamKemDvktLearner())->nap([
            ['loai' => 'XX', 'ma_vat_pham' => '40.540', 'ma_dvkt' => '18.0150.0041'],
        ]);

        $this->assertCount(1, $kq['loi']);
        $this->assertSame(0, $this->phienBan());
    }

    /** @test */
    public function job_kiem_xoa_bo_dem_khi_phien_ban_doi()
    {
        $this->boot([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152843_create_xml3176_xml5s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
        ]);
        $this->app->instance(Xml3176ErrorService::class, new FakeXml3176ErrorService());

        $dem = 0;
        $nho = function () use (&$dem) {
            Xml3176BoDem::nho('t', 'k', function () use (&$dem) {
                return ++$dem;
            });

            return $dem;
        };

        (new CheckXml3176TypeJob('L1', 'XML5'))->handle();
        $nho();
        (new CheckXml3176TypeJob('L1', 'XML5'))->handle();
        $this->assertSame(1, $nho(), 'phien ban khong doi -> giu bo dem');

        // Web sua danh muc (tien trinh khac).
        DB::table('xml3176_phien_ban_danh_muc')->where('id', 1)->update(['phien_ban' => 3]);

        (new CheckXml3176TypeJob('L1', 'XML5'))->handle();
        $this->assertSame(2, $nho(), 'phien ban doi -> job xoa bo dem truoc khi kiem');
    }

    /** @test */
    public function job_hoan_tat_va_lenh_do_kiem_goi_dong_bo()
    {
        foreach (['Jobs/CheckCompleteXml3176RecordJob.php', 'Console/Commands/Xml3176DoKiem.php'] as $tep) {
            $this->assertContains('Xml3176PhienBanDanhMuc::dongBoBoDem()', file_get_contents(app_path($tep)), $tep);
        }
    }

    /** @test */
    public function hoc_lai_nap_va_cac_thao_tac_quan_tri_khac_deu_tang()
    {
        // Phu nguon: cac duong con lai can du lieu lon (hoc ~1,4 trieu dong) hoac Excel.
        $quanTri = file_get_contents(app_path('Services/Xml3176/VatPhamKemDvktQuanTri.php'));
        foreach (['datHieuLuc', 'datGhiChu', 'xoa', 'themBatBuoc', 'themDongTay'] as $ham) {
            // Than ham: tu "function ten(" toi ham ke tiep cua lop.
            $this->assertRegExp('/function ' . $ham . '\((?:(?!\n    (?:public|private|protected) ).)*Xml3176PhienBanDanhMuc::tang\(/s', $quanTri, $ham);
        }
        $this->assertContains('Xml3176PhienBanDanhMuc::tang(', file_get_contents(app_path('Services/Xml3176/VatPhamKemDvktHocLaiService.php')));
        $this->assertContains('Xml3176PhienBanDanhMuc::tang(', file_get_contents(app_path('Services/Xml3176/VatPhamKemDvktLearner.php')));
        $this->assertContains('Xml3176PhienBanDanhMuc::tang(', file_get_contents(app_path('Console/Commands/HanhChinhChuyen2Cap.php')));
    }
}
