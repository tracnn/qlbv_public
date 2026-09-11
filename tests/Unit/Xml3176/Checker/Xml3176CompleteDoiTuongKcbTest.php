<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteDoiTuongKcbTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    private function codes(Xml3176Xml1 $x1): array
    {
        return $this->errorCodes($this->invokePrivate($this->checker(), 'checkDoiTuongKcbMucHuong', $x1));
    }

    /** @test */
    public function ma_12_bat_buoc_muc_huong_100()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '1.2', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'A', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 80]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', $this->codes($x1));
    }

    /** @test */
    public function ma_12_khai_dung_100_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1, 'ma_doituong_kcb' => '1.2', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', $this->codes($x1));
    }

    /** @test */
    public function ma_113_tu_moc_01_07_2026_phai_huong_50()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1, 'ma_doituong_kcb' => '1.13', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 80]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ma_113_khai_dung_50_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'D', 'stt' => 1, 'ma_doituong_kcb' => '1.13', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'D', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 50]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ma_113_truoc_moc_thi_khong_duoc_huong()
    {
        // Truoc 01/7/2026: khong duoc huong BHYT, nen T_BHTT phai bang 0.
        $x1 = Xml3176Xml1::create(['ma_lk' => 'E', 'stt' => 1, 'ma_doituong_kcb' => '1.13',
            'ngay_vao' => '202601150800', 't_bhtt' => 500000]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ngay_vao_khong_doc_duoc_thi_im_lang()
    {
        // Thieu can cu thi khong ket luan.
        $x1 = Xml3176Xml1::create(['ma_lk' => 'F', 'stt' => 1, 'ma_doituong_kcb' => '1.13',
            'ngay_vao' => '', 't_bhtt' => 500000]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function linh_thuoc_khong_kham_ma_van_co_tien_cong_kham()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'G', 'stt' => 1, 'ma_doituong_kcb' => '7', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'G', 'stt' => 1, 'ma_dich_vu' => 'KHAM', 'ma_nhom' => 13, 'muc_huong' => 100]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', $this->codes($x1));
    }

    /** @test */
    public function linh_thuoc_khong_co_dong_kham_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'H', 'stt' => 1, 'ma_doituong_kcb' => '10', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'H', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'muc_huong' => 100]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', $this->codes($x1));
    }

    /** @test */
    public function ma_thuong_khong_sinh_loi_nao()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'I', 'stt' => 1, 'ma_doituong_kcb' => '1.5', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'I', 'stt' => 1, 'ma_dich_vu' => 'KHAM', 'ma_nhom' => 13, 'muc_huong' => 80]);

        $this->assertSame([], $this->codes($x1));
    }

    /** @test */
    public function ma_rong_hoac_ngoai_danh_muc_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'J', 'stt' => 1, 'ma_doituong_kcb' => '', 'ngay_vao' => '202609010800']);
        $this->assertSame([], $this->codes($x1));

        $x2 = Xml3176Xml1::create(['ma_lk' => 'K', 'stt' => 1, 'ma_doituong_kcb' => '9.9', 'ngay_vao' => '202609010800']);
        $this->assertSame([], $this->codes($x2));
    }
}
