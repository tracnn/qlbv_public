<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\CommonValidationService;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026: mot loi XML1 chi ra MOT dong. Quy tac cu (ADMIN_INFO_ERROR_*)
 * nhuong quy tac moi (DOI_TUONG_KCB_*) khi quy tac moi da bao dung loi do.
 */
class TrungLapXml1Test extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
    }

    /** Chay infoChecker + checkDoiTuongKcb (hai noi dat hai quy tac trung). */
    private function codes(array $ghiDe): array
    {
        // Danh muc gia: moi ma deu "co trong danh muc" de khong cham CSDL.
        $cvs = new class extends CommonValidationService {
            public function isMedicalOrganizationValid($value) { return true; }
            public function isIcd10CategoryValid($value) { return true; }
            public function isIcdYhctCategoryValid($value) { return true; }
            public function isIcdYhctCategoryValue($value) { return true; }
            public function isAdministrativeUnitProvinceValid($value) { return true; }
            public function isAdministrativeUnitCommuneValid($value) { return true; }
            public function isAdministrativeUnitWardInProvinceValid($p, $c) { return true; }
            public function isJobCategoryValid($value) { return true; }
            public function danhMucBenhPl1() { return []; }
        };
        $checker = new Xml3176Xml1Checker(new FakeXml3176ErrorService(), $cvs);

        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ma_dkbd' => '36001',
            'ma_loai_kcb' => '03',
        ], $ghiDe));

        return array_merge(
            $this->errorCodes($this->invokePrivate($checker, 'infoChecker', $dong)),
            $this->errorCodes($this->invokePrivate($checker, 'checkDoiTuongKcb', $dong))
        );
    }

    // ---- A2: GIAY_CHUYEN_TUYEN ----

    /** @test */
    public function ma_13_co_noi_di_thieu_giay_chuyen_tuyen_chi_ra_mot_dong()
    {
        $codes = $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001', 'giay_chuyen_tuyen' => '']);

        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN', $codes);
        $this->assertNotContains('XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN', $codes);
    }

    /** @test */
    public function ma_15_co_noi_di_thieu_giay_chuyen_tuyen_chi_ra_mot_dong()
    {
        $codes = $this->codes(['ma_doituong_kcb' => '1.5', 'ma_noi_di' => '36001', 'giay_chuyen_tuyen' => null]);

        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN', $codes);
        $this->assertNotContains('XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN', $codes);
    }

    /** @test */
    public function ma_khong_doi_giay_chuyen_tuyen_van_bao_quy_tac_cu()
    {
        // Ma 2 (cap cuu) khong co can_giay_chuyen_tuyen: quy tac moi im lang, quy tac cu giu nguyen.
        $codes = $this->codes(['ma_doituong_kcb' => '2', 'ma_noi_di' => '36001', 'giay_chuyen_tuyen' => '']);

        $this->assertContains('XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN', $codes);
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN', $codes);
    }

    // ---- B6: DUNG_DKBD_SAI_MA vs MA_DOITUONG_KCB_INVALID ----

    /** @test */
    public function ma_31_dung_dkbd_chi_ra_mot_dong()
    {
        $codes = $this->codes(['ma_doituong_kcb' => '3.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']);

        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', $codes);
        $this->assertNotContains('XML1_ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID', $codes);
    }

    /** @test */
    public function ma_31_dkbd_benh_vien_nhung_cskcb_rong_van_bao_quy_tac_cu()
    {
        // Quy tac moi can MA_CSKCB; thieu thi im lang -> quy tac cu phai giu loi.
        $codes = $this->codes(['ma_doituong_kcb' => '3.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '']);

        $this->assertContains('XML1_ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID', $codes);
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', $codes);
    }

    /** @test */
    public function ma_31_dkbd_benh_vien_cskcb_khac_van_bao_quy_tac_cu()
    {
        $codes = $this->codes(['ma_doituong_kcb' => '3.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '36001']);

        $this->assertContains('XML1_ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID', $codes);
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', $codes);
    }
}
