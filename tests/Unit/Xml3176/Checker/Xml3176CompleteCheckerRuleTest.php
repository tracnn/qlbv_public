<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteCheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
            '2024_07_05_113054_create_medical_organizations_table.php',
            '2026_07_30_090000_add_tuyen_cmkt_hang_benh_vien_to_medical_organizations.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    /** @test */
    public function quy_tac_thieu_chuyen_tuyen_hen_kham_lai_da_bi_go()
    {
        // MA_NOI_DI la truong DAU VAO, XML13/XML14 la chung tu DAU RA - co cai nay khong
        // keo theo phai co cai kia. Do tren du lieu that: 81/81 loi deu la bao oan.
        $this->assertFalse(
            method_exists(Xml3176CompleteChecker::class, 'checkMissingTransferOrAppointment'),
            'Quy tac checkMissingTransferOrAppointment da bi go, khong duoc dung lai'
        );

        $src = file_get_contents(app_path('Services/Xml3176CompleteChecker.php'));
        $this->assertNotContains("generateErrorCode('MISSING_TRANSFER_OR_APPOINTMENT')", $src);
    }

    /** @test */
    public function ngay_kq_xml4_khac_xml3()
    {
        Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607010900']);
        Xml3176Xml4::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607020900']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkXml4NgayKqMismatchXml3', 'B'));
        $this->assertContains('XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3', $codes);
    }

    /** @test */
    public function pt_lan_2_thanh_toan_100()
    {
        Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 1, 'ma_dich_vu' => 'PT', 'ma_nhom' => 8, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 2, 'ma_dich_vu' => 'PT', 'ma_nhom' => 18, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'C'));
        $this->assertContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }

    /** @test */
    public function dong_ngoai_nhom_pttt_khong_bi_coi_la_pttt_du_co_ma_pttt()
    {
        // Bo xuat HIS dien MA_PTTT cho gan nhu moi dong: do tren du lieu that, 28.169
        // dong co MA_PTTT nhung chi 2.487 (8,8%) thuoc nhom 8/18. Can cu theo MA_PTTT
        // lam quy tac bao "PTTT lan 2..100 trong ngay" cho ca xet nghiem, VTYT, giuong -
        // 24.954 loi tren 914/1153 ho so, gan het la bao oan.
        Xml3176Xml1::create(['ma_lk' => 'XN', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 2, 'ma_dich_vu' => 'XN2', 'ma_nhom' => 1, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'XN', 'stt' => 3, 'ma_dich_vu' => 'VT1', 'ma_nhom' => 10, 'ma_pttt' => 'PT03', 'ngay_yl' => '202607011200', 'tyle_tt_dv' => '100']);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'XN'));

        $this->assertNotContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }

    /** @test */
    public function chi_dem_dong_thuoc_nhom_pttt_khi_xep_thu_tu_trong_ngay()
    {
        // Dong xet nghiem xen giua khong duoc lam dong PTTT thu hai bi tinh thanh thu ba,
        // va mot minh dong PTTT duy nhat trong ngay thi khong co "lan 2" nao.
        Xml3176Xml1::create(['ma_lk' => 'MOT', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'MOT', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'MOT', 'stt' => 2, 'ma_dich_vu' => 'PT1', 'ma_nhom' => 8, 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'MOT'));

        $this->assertNotContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }

    /** @test */
    public function ma_12_muc_huong_co_dinh_thi_khong_bi_soi_tran_theo_the()
    {
        // Ma 1.2: muc_huong_co_dinh = 100, khong phu thuoc quyen loi tren the. Truoc khi
        // sua, checkMucHuong xep 1.2 vao nhanh "dung tuyen" va lay tran bang quyen loi
        // the (DN4... -> 80): khai MUC_HUONG = 100 se bi bao vuot tran, du day chinh la
        // muc huong dung theo danh muc. Sau khi sua, checkMucHuong phai nhuong han cho
        // DOI_TUONG_KCB_MUC_HUONG_CO_DINH va im lang o day.
        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'M12', 'stt' => 1,
            'ma_doituong_kcb' => '1.2',
            'ma_loai_kcb' => '03',
            'ma_the_bhyt' => 'DN4010112345678', // quyen loi 80%
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000,
        ]);
        Xml3176Xml3::create(['ma_lk' => 'M12', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMucHuong', $x1));

        $this->assertNotContains('XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT', $codes);
    }

    /** @test */
    public function ma_36_huong_100_khong_bi_coi_la_trai_tuyen()
    {
        // Danh muc: chi 3.1 bi giam muc huong. 3.2/3.3/3.6 deu huong 100%.
        // Config cu khai ['3'] va khop TIEN TO nen gom ca bon ma.
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);

        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'M36', 'stt' => 1,
            'ma_doituong_kcb' => '3.6',
            'ma_loai_kcb' => '03',
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000,
        ]);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMucHuong', $x1));

        $this->assertNotContains('XMLComplete_MUC_HUONG_TRAI_TUYEN_TW', $codes);
    }

    /** @test */
    public function khong_con_khop_tien_to_trong_ma_nguon()
    {
        // Ca chong hoi quy grep ma nguon - gia tri rieng, giu nguyen.
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);

        $src = file_get_contents(app_path('Services/Xml3176CompleteChecker.php'));
        $this->assertNotContains('strpos($maDoiTuong', $src,
            'checkMucHuong van con khop tien to - phai doi sang khop dung bang');

        $src1 = file_get_contents(app_path('Services/Xml3176Xml1Checker.php'));
        $this->assertNotContains("strpos(\$data->ma_doituong_kcb", $src1,
            'Xml3176Xml1Checker van con khop tien to - phai doi sang khop dung bang');
    }

    /** @test */
    public function ma_31_van_duoc_coi_la_trai_tuyen()
    {
        // Ca hanh vi that: ca grep o tren khong dung sinh ho so nao, nen neu ai doi cau
        // hinh trai tuyen thanh [] thi mã 3.1 het bi soi tran 40% ma toan bo test van
        // xanh. Dung phai dung ho so 3.1 thuc su de khoa hanh vi.
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);

        MedicalOrganization::create([
            'ma_cskcb' => '01929',
            'ten_cskcb' => 'BV test',
            'dia_chi_cskcb' => 'dia chi test',
            'tuyen_cmkt' => '1', // config('xml3176.muc_huong.tuyen_tw_values') = ['1']
        ]);

        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'M31', 'stt' => 1,
            'ma_doituong_kcb' => '3.1',
            'ma_loai_kcb' => '03', // noi tru
            'ma_the_bhyt' => 'DN4010112345678', // quyen loi 80%
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000,
        ]);
        Xml3176Xml3::create(['ma_lk' => 'M31', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMucHuong', $x1));

        $this->assertContains('XMLComplete_MUC_HUONG_TRAI_TUYEN_TW', $codes);
    }
}
