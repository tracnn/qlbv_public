<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Services\CommonValidationService;
use App\Services\Xml3176\Xml3176BoDem;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DanhMucNhoDemTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var CommonValidationService */
    private $sv;

    protected function setUp()
    {
        parent::setUp();

        $this->bootXml3176Sqlite([
            '2024_07_04_072537_create_icd10_categories_table.php',
            '2024_07_04_092500_create_icdyhct_categories_table.php',
            '2024_07_05_113054_create_medical_organizations_table.php',
            '2024_07_07_221139_create_administrative_units_table.php',
            '2024_07_08_144524_create_job_categories_table.php',
            '2024_06_14_084449_create_trang_thiet_bi_table.php',
            '2026_09_10_100000_create_dvkt_can_ma_may_table.php',
        ]);

        DB::table('icd10_categories')->insert([
            ['icd_code' => 'A00', 'icd_name' => 'x', 'is_active' => 1],
            ['icd_code' => 'B00', 'icd_name' => 'y', 'is_active' => 0],
        ]);
        DB::table('icd_yhct_categories')->insert([
            ['icd_code' => 'YH1', 'icd_name' => 'x', 'icd_yhct_name' => 'y', 'icd10_code' => 'A00', 'is_active' => 1],
        ]);
        DB::table('medical_organizations')->insert([
            ['ma_cskcb' => '01001', 'ten_cskcb' => 'a', 'dia_chi_cskcb' => 'b', 'is_active' => 1],
        ]);
        DB::table('administrative_units')->insert([
            ['province_code' => '01', 'province_name' => 'HN', 'district_code' => '001', 'district_name' => 'q',
             'commune_code' => '00001', 'commune_name' => 'p', 'is_active' => 1],
        ]);
        DB::table('job_categories')->insert([['job_code' => '11', 'job_name' => 'n']]);
        DB::table('equipment_catalogs')->insert([[
            'ten_tb' => 't', 'ky_hieu' => 'k', 'congty_sx' => 'c', 'nuoc_sx' => 'n', 'nam_sx' => 2020,
            'nam_sd' => 2021, 'ma_may' => 'MM1', 'so_luu_hanh' => 's',
        ]]);
        DB::table('dvkt_can_ma_may')->insert([
            ['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 1],
        ]);

        $this->sv = app(CommonValidationService::class);
    }

    /** Moi nhom: [goi voi khoa co trong danh muc, goi voi khoa khong co (hoac null)]. */
    private function nhom()
    {
        return [
            'icd10' => [
                function ($s) { return $s->isIcd10CategoryValid('A00'); },
                function ($s) { return $s->isIcd10CategoryValid('B00'); },
            ],
            'icdyhct' => [
                function ($s) { return $s->isIcdYhctCategoryValid('YH1'); },
                function ($s) { return $s->isIcdYhctCategoryValid('ZZ'); },
            ],
            'cskcb' => [
                function ($s) { return $s->isMedicalOrganizationValid('01001'); },
                function ($s) { return $s->isMedicalOrganizationValid('99999'); },
            ],
            'tinh' => [
                function ($s) { return $s->isAdministrativeUnitProvinceValid('01'); },
                function ($s) { return $s->isAdministrativeUnitProvinceValid('99'); },
            ],
            'huyen' => [
                function ($s) { return $s->isAdministrativeUnitDistrictValid('001'); },
                function ($s) { return $s->isAdministrativeUnitDistrictValid('999'); },
            ],
            'xa' => [
                function ($s) { return $s->isAdministrativeUnitCommuneValid('00001'); },
                function ($s) { return $s->isAdministrativeUnitCommuneValid('99999'); },
            ],
            'huyen_trong_tinh' => [
                function ($s) { return $s->isAdministrativeUnitDistrictInProvinceValid('01', '001'); },
                function ($s) { return $s->isAdministrativeUnitDistrictInProvinceValid('02', '001'); },
            ],
            'xa_trong_huyen' => [
                function ($s) { return $s->isAdministrativeUnitWardInDistrictValid('001', '00001'); },
                function ($s) { return $s->isAdministrativeUnitWardInDistrictValid('002', '00001'); },
            ],
            'xa_trong_tinh' => [
                function ($s) { return $s->isAdministrativeUnitWardInProvinceValid('01', '00001'); },
                function ($s) { return $s->isAdministrativeUnitWardInProvinceValid('02', '00001'); },
            ],
            'nghe' => [
                function ($s) { return $s->isJobCategoryValid('11'); },
                function ($s) { return $s->isJobCategoryValid('99'); },
            ],
            'dvkt_can_ma_may' => [
                function ($s) { return $s->isDvktCanMaMay('18.0015.0001'); },
                function ($s) { return $s->isDvktCanMaMay('00.0000.0000'); },
            ],
            'ma_may' => [
                function ($s) { return $s->isEquipmentMaMayValid('MM1'); },
                function ($s) { return $s->isEquipmentMaMayValid('KHONG'); },
            ],
            'co_danh_muc' => [
                function ($s) { return $s->coDanhMucDvktCanMaMay(); },
                null,
            ],
        ];
    }

    private function demTruyVan(callable $f)
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $f();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    /** @test */
    public function goi_lap_lai_chi_mot_truy_van()
    {
        foreach ($this->nhom() as $ten => $cap) {
            foreach ($cap as $i => $ham) {
                if ($ham === null) {
                    continue;
                }
                Xml3176BoDem::xoaTatCa();
                $n = $this->demTruyVan(function () use ($ham) {
                    for ($k = 0; $k < 20; $k++) {
                        $ham($this->sv);
                    }
                });
                $this->assertSame(1, $n, $ten . ' #' . $i . ' phai chi 1 truy van');
            }
        }
    }

    /** @test */
    public function ket_qua_giong_truy_van_goc()
    {
        // Hai luot: luot dau doc CSDL, luot sau lay tu bo dem - cung ket qua.
        foreach ([1, 2] as $luot) {
            foreach ($this->nhom() as $ten => $cap) {
                $this->assertTrue($cap[0]($this->sv), $ten . ' co, luot ' . $luot);
                if ($cap[1] !== null) {
                    $this->assertFalse($cap[1]($this->sv), $ten . ' khong, luot ' . $luot);
                }
            }
        }
    }

    /** @test */
    public function icdyhct_dong_tra_ve_dong_hoac_null()
    {
        $this->assertSame('A00', $this->sv->isIcdYhctCategoryValue('YH1')->icd10_code);
        $this->assertSame('A00', $this->sv->isIcdYhctCategoryValue('YH1')->icd10_code);
        $this->assertNull($this->sv->isIcdYhctCategoryValue('ZZ'));
        $this->assertNull($this->sv->isIcdYhctCategoryValue('ZZ'));
    }

    /** @test */
    public function khoa_khac_nhau_khong_tra_nham()
    {
        $this->assertTrue($this->sv->isIcd10CategoryValid('A00'));
        $this->assertFalse($this->sv->isIcd10CategoryValid(''));
        $this->assertFalse($this->sv->isIcd10CategoryValid(null));
        $this->assertTrue($this->sv->isIcd10CategoryValid('A00'));
    }

    /** @test */
    public function danh_muc_can_ma_may_rong_giu_nhanh_lui()
    {
        DB::table('dvkt_can_ma_may')->delete();
        Xml3176BoDem::xoaTatCa();

        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay());
        DB::table('dvkt_can_ma_may')->insert([
            ['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 1],
        ]);
        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay(), 'con trong han dem');

        Xml3176BoDem::xoaTatCa();
        $this->assertTrue($this->sv->coDanhMucDvktCanMaMay());
    }
}
