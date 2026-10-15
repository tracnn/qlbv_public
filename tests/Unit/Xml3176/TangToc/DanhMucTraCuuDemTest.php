<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176\DanhMucTraCuu;
use App\Services\Xml3176Xml3Checker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DanhMucTraCuuDemTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2024_07_05_113054_create_medical_organizations_table.php',
        ]);

        foreach ([
            'medicine_catalogs' => ['ma_thuoc', 'ham_luong', 'so_dang_ky', 'tt_thau', 'ma_cskcb'],
            'medical_supply_catalogs' => ['ma_vat_tu', 'tt_thau', 'ma_cskcb'],
        ] as $bang => $cot) {
            Schema::connection('mysql')->create($bang, function ($t) use ($cot) {
                $t->increments('id');
                foreach ($cot as $c) {
                    $t->string($c)->nullable();
                }
            });
        }
    }

    private function dvkt($ma, $cs, $tu = '20240101', $den = null)
    {
        DB::table('service_catalogs')->insert([
            'ma_dich_vu' => $ma, 'ten_dich_vu' => 'x', 'don_gia' => 1, 'quy_trinh' => '',
            'tu_ngay' => $tu, 'den_ngay' => $den, 'ma_cskcb' => $cs,
        ]);
    }

    /** Dem so truy van SQL chay trong $fn. */
    private function demTruyVan(callable $fn): int
    {
        $n = 0;
        DB::connection('mysql')->listen(function () use (&$n) {
            $n++;
        });
        $fn();

        return $n;
    }

    /** @test */
    public function dvkt_ton_tai_nho_theo_co_so_va_ma()
    {
        $this->dvkt('M1', '01001');

        $this->assertTrue(DanhMucTraCuu::dvktTonTai('01001', 'M1'));
        $this->assertFalse(DanhMucTraCuu::dvktTonTai('01002', 'M1'));
        $this->assertFalse(DanhMucTraCuu::dvktTonTai('01001', 'M2'));
        // Dong chung (khong gan co so) hien voi moi co so.
        $this->dvkt('M3', null);
        $this->assertTrue(DanhMucTraCuu::dvktTonTai('01002', 'M3'));
    }

    /** @test */
    public function dvkt_hieu_luc_nho_theo_ngay_vao()
    {
        $this->dvkt('M1', null, '20240101', '20241231');

        $this->assertCount(1, DanhMucTraCuu::dvktHieuLuc('01001', 'M1', '20240601'));
        $this->assertCount(0, DanhMucTraCuu::dvktHieuLuc('01001', 'M1', '20250601'));
        $this->assertCount(1, DanhMucTraCuu::dvktHieuLuc('01001', 'M1', '20240601'));
    }

    /** @test */
    public function ma_hau_to_tra_ma_goc_dung()
    {
        $this->dvkt('18.0659.0054', null);

        $x1 = new Xml3176Xml1(['ma_lk' => 'A', 'ma_cskcb' => null, 'ngay_vao' => '202610050800']);
        $dong = new Xml3176Xml3(['ma_lk' => 'A', 'stt' => 1, 'ma_nhom' => 8,
            'ma_dich_vu' => '18.0659.0054_TB', 'ten_dich_vu' => 'x', 'don_gia_bh' => 1]);
        $dong->setRelation('Xml3176Xml1', $x1);
        $checker = $this->makeChecker(Xml3176Xml3Checker::class);

        $lan = function () use ($checker, $dong) {
            return $this->errorCodes($this->invokePrivate($checker, 'checkMedicalService', $dong));
        };
        $dau = $lan();
        $sau = $lan();

        $this->assertSame($dau, $sau);
        $this->assertNotContains('XML3_INVALID_MA_DICH_VU', $dau);
        $this->assertFalse(DanhMucTraCuu::dvktTonTai(null, '18.0659.0054_TB'));
        $this->assertTrue(DanhMucTraCuu::dvktTonTai(null, '18.0659.0054'));
    }

    /** @test */
    public function thuoc_va_vtyt_nho_theo_khoa()
    {
        DB::table('medicine_catalogs')->insert([
            ['ma_thuoc' => 'T1', 'ham_luong' => '1g', 'so_dang_ky' => 'S1', 'tt_thau' => 'Q;G;N;2025', 'ma_cskcb' => '01001'],
        ]);
        DB::table('medical_supply_catalogs')->insert([
            ['ma_vat_tu' => 'V1', 'tt_thau' => 'Q;G;N;2025', 'ma_cskcb' => '01001'],
        ]);

        $this->assertNotNull(DanhMucTraCuu::thuocKhop('01001', 'T1', '1g', 'S1', 'Q;G;N;2025'));
        $this->assertNull(DanhMucTraCuu::thuocKhop('01002', 'T1', '1g', 'S1', 'Q;G;N;2025'));
        $this->assertNull(DanhMucTraCuu::thuocKhop('01001', 'T1', '2g', 'S1', 'Q;G;N;2025'));
        $this->assertSame(['1g'], DanhMucTraCuu::thuocHamLuongDuyet('01001', 'T1', 'S1', 'Q;G;N;2025')->all());
        $this->assertTrue(DanhMucTraCuu::thuocTonTai('01001', 'T1'));
        $this->assertTrue(DanhMucTraCuu::thuocTonTai('01001', 'T1', '1g'));
        $this->assertFalse(DanhMucTraCuu::thuocTonTai('01001', 'T1', '2g'));
        $this->assertFalse(DanhMucTraCuu::thuocTonTai('01001', 'T1', '1g', 'S2'));
        $this->assertTrue(DanhMucTraCuu::thuocTonTai('01001', 'T1', '1g', 'S1'));

        $this->assertCount(1, DanhMucTraCuu::vtyt('01001', 'V1'));
        $this->assertCount(0, DanhMucTraCuu::vtyt('01002', 'V1'));
        $this->assertCount(0, DanhMucTraCuu::vtyt('01001', 'V2'));
    }

    /** @test */
    public function lap_lai_mot_truy_van()
    {
        $this->dvkt('M1', null);
        DB::table('medicine_catalogs')->insert([
            ['ma_thuoc' => 'T1', 'ham_luong' => '1g', 'so_dang_ky' => 'S1', 'tt_thau' => 'Q;G;N;2025', 'ma_cskcb' => null],
        ]);

        $n = $this->demTruyVan(function () {
            for ($i = 0; $i < 3; $i++) {
                DanhMucTraCuu::dvktTonTai('01001', 'M1');
                DanhMucTraCuu::dvktTonTai('01001', 'KHONG');
                DanhMucTraCuu::dvktHieuLuc('01001', 'M1', '20250101');
                DanhMucTraCuu::vtyt('01001', 'KHONG');
                DanhMucTraCuu::thuocKhop('01001', 'T1', '9g', 'S1', 'Q;G;N;2025');
                DanhMucTraCuu::thuocTonTai('01001', 'T1');
            }
        });

        $this->assertSame(6, $n, 'moi tra cuu (ke ca ket qua rong/null/false) chi chay SQL mot lan');
    }

    /** @test */
    public function migration_chi_muc_chay_lai_an_toan()
    {
        require_once base_path('database/migrations/2026_10_15_110000_them_chi_muc_ma_dich_vu_service_catalogs.php');
        $mig = new \ThemChiMucMaDichVuServiceCatalogs();
        $ten = function () {
            return array_map(function ($d) {
                return $d->name;
            }, DB::select("PRAGMA index_list('service_catalogs')"));
        };

        $mig->up();
        $mig->up();
        $this->assertContains('service_catalogs_ma_dich_vu_ma_cskcb_index', $ten());

        $mig->down();
        $mig->down();
        $this->assertNotContains('service_catalogs_ma_dich_vu_ma_cskcb_index', $ten());
    }

    /** @test */
    public function migration_chi_muc_loi_tao_khong_nem_ra_ngoai()
    {
        require_once base_path('database/migrations/2026_10_15_110000_them_chi_muc_ma_dich_vu_service_catalogs.php');
        $mig = new class extends \ThemChiMucMaDichVuServiceCatalogs {
            protected function taoChiMuc()
            {
                throw new \Illuminate\Database\QueryException('create index', [], new \Exception('loi gia lap'));
            }
        };

        $mig->up();

        $ten = array_map(function ($d) {
            return $d->name;
        }, DB::select("PRAGMA index_list('service_catalogs')"));
        $this->assertNotContains('service_catalogs_ma_dich_vu_ma_cskcb_index', $ten);
    }
}
