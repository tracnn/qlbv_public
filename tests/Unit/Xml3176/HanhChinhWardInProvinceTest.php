<?php

namespace Tests\Unit\Xml3176;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class HanhChinhWardInProvinceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var CommonValidationService */
    private $sv;

    protected function setUp()
    {
        parent::setUp();

        $this->bootXml3176Sqlite(['2024_07_07_221139_create_administrative_units_table.php']);

        // Migration goc khai district NOT NULL nen phai dien gia tri; cot huyen khong
        // tham gia phep kiem tra nay.
        DB::table('administrative_units')->insert([
            ['province_code' => '01', 'province_name' => 'Hà Nội', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '00001', 'commune_name' => 'Phường A',
             'is_active' => 1],
            ['province_code' => '96', 'province_name' => 'Cà Mau', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '99999', 'commune_name' => 'Xã B',
             'is_active' => 1],
            ['province_code' => '01', 'province_name' => 'Hà Nội', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '00777', 'commune_name' => 'Phường cũ',
             'is_active' => 0],
        ]);

        $this->sv = app(CommonValidationService::class);
    }

    /** @test */
    public function dung_khi_xa_thuoc_tinh_va_dang_hoat_dong()
    {
        $this->assertTrue($this->sv->isAdministrativeUnitWardInProvinceValid('01', '00001'));
    }

    /** @test */
    public function sai_khi_xa_thuoc_tinh_khac()
    {
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', '99999'));
    }

    /** @test */
    public function sai_khi_dong_da_nghi_huu()
    {
        // Dong dung tinh dung xa nhung is_active = 0 -> coi nhu khong con
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', '00777'));
    }

    /** @test */
    public function sai_khi_ma_rong()
    {
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('', '00001'));
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', ''));
    }
}
