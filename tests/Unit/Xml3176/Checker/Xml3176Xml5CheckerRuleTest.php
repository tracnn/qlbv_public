<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml5;
use App\Services\Xml3176Xml5Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml5CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152843_create_xml3176_xml5s_table.php']);
    }

    /** @test */
    public function bat_dien_bien_trung_o_dong_stt_lon_hon()
    {
        Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 1, 'dien_bien_ls' => 'Bệnh ổn định', 'thoi_diem_dbls' => '202609180800']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 2, 'dien_bien_ls' => 'bệnh  ổn định', 'thoi_diem_dbls' => '202609181600']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }

    /** @test */
    public function khong_bat_khi_khac_nhau()
    {
        Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 1, 'dien_bien_ls' => 'Sốt cao']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 2, 'dien_bien_ls' => 'Hết sốt']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertNotContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }

    /** @test */
    public function trung_noi_dung_nhung_khac_ngay_khong_bao()
    {
        // Diễn biến thường quy lặp lại mỗi ngày ("Thuốc thường quy; CSC3...") là bình thường -
        // chỉ trùng TRONG CÙNG MỘT NGÀY mới là ghi chép sao chép.
        Xml3176Xml5::create(['ma_lk' => 'N', 'stt' => 43, 'dien_bien_ls' => 'Thuốc thường quy', 'thoi_diem_dbls' => '202609180800']);
        $row = Xml3176Xml5::create(['ma_lk' => 'N', 'stt' => 68, 'dien_bien_ls' => 'Thuốc thường quy', 'thoi_diem_dbls' => '202609190800']);

        $codes = $this->errorCodes($this->invokePrivate($this->makeChecker(Xml3176Xml5Checker::class), 'checkDienBienDuplicate', $row));

        $this->assertNotContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }

    /** @test */
    public function trung_trong_cung_ngay_moi_bao_va_neu_ngay()
    {
        Xml3176Xml5::create(['ma_lk' => 'P', 'stt' => 1, 'dien_bien_ls' => 'Thuốc thường quy', 'thoi_diem_dbls' => '202609170800']);
        Xml3176Xml5::create(['ma_lk' => 'P', 'stt' => 2, 'dien_bien_ls' => 'Thuốc thường quy', 'thoi_diem_dbls' => '202609180700']);
        $row = Xml3176Xml5::create(['ma_lk' => 'P', 'stt' => 3, 'dien_bien_ls' => 'Thuốc thường quy', 'thoi_diem_dbls' => '202609181900']);

        $loi = collect($this->invokePrivate($this->makeChecker(Xml3176Xml5Checker::class), 'checkDienBienDuplicate', $row));

        $this->assertSame(['XML5_DIEN_BIEN_DUPLICATE'], $loi->pluck('error_code')->all());
        $this->assertContains('STT 2', $loi[0]->description);        // dòng cùng ngày, không phải STT 1
        $this->assertContains('18/09/2026', $loi[0]->description);
    }

    /** @test */
    public function thieu_thoi_diem_thi_khong_xac_dinh_duoc_ngay_khong_bao()
    {
        Xml3176Xml5::create(['ma_lk' => 'Q', 'stt' => 1, 'dien_bien_ls' => 'Ổn định', 'thoi_diem_dbls' => null]);
        $row = Xml3176Xml5::create(['ma_lk' => 'Q', 'stt' => 2, 'dien_bien_ls' => 'Ổn định', 'thoi_diem_dbls' => null]);

        $codes = $this->errorCodes($this->invokePrivate($this->makeChecker(Xml3176Xml5Checker::class), 'checkDienBienDuplicate', $row));

        $this->assertNotContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }
}
