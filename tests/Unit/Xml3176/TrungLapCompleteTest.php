<?php

namespace Tests\Unit\Xml3176;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml14;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026: cac quy tac XMLComplete bao trung lap cung mot sai sot.
 */
class TrungLapCompleteTest extends TestCase
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
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    private function goi(string $method, ...$args): array
    {
        return $this->errorCodes($this->invokePrivate($this->checker(), $method, ...$args));
    }

    // ---- A5: XML4_NGAY_KQ_MISMATCH_XML3 ----

    /** @test */
    public function a5_nhieu_dong_xml4_cung_dich_vu_cung_ngay_chi_bao_mot_lan()
    {
        Xml3176Xml1::create(['ma_lk' => 'A5', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'A5', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607010900']);
        foreach (['CS1', 'CS2', 'CS3'] as $i => $cs) {
            Xml3176Xml4::create(['ma_lk' => 'A5', 'stt' => $i + 1, 'ma_dich_vu' => 'DV1', 'ma_chi_so' => $cs, 'ngay_kq' => '202607020900']);
        }

        $codes = $this->goi('checkXml4NgayKqMismatchXml3', 'A5');

        $this->assertSame(['XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3'], $codes);
    }

    /** @test */
    public function a5_hai_ngay_lech_khac_nhau_van_bao_hai_lan()
    {
        Xml3176Xml1::create(['ma_lk' => 'A5B', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'A5B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607010900']);
        Xml3176Xml4::create(['ma_lk' => 'A5B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ma_chi_so' => 'CS1', 'ngay_kq' => '202607020900']);
        Xml3176Xml4::create(['ma_lk' => 'A5B', 'stt' => 2, 'ma_dich_vu' => 'DV1', 'ma_chi_so' => 'CS2', 'ngay_kq' => '202607030900']);

        $this->assertCount(2, $this->goi('checkXml4NgayKqMismatchXml3', 'A5B'));
    }

    // ---- B7: DOI_TUONG_KCB_MUC_HUONG_THEO_MOC vs MUC_HUONG_EXCEEDS_ENTITLEMENT ----

    /** @test */
    public function b7_ma_113_tu_moc_chi_bao_theo_moc_khong_bao_vuot_tran_the()
    {
        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'B7', 'stt' => 1,
            'ma_doituong_kcb' => '1.13',
            'ma_loai_kcb' => '01',
            'ma_the_bhyt' => 'DN4010112345678', // quyen loi 80%
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000, // >= 15% luong co so
        ]);
        Xml3176Xml3::create(['ma_lk' => 'B7', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->goi('checkDoiTuongKcbMucHuong', $x1));
        $this->assertNotContains('XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT', $this->goi('checkMucHuong', $x1));
    }

    /** @test */
    public function b7_ma_thuong_van_bi_soi_vuot_tran_the()
    {
        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'B7B', 'stt' => 1,
            'ma_doituong_kcb' => '1.1',
            'ma_loai_kcb' => '01',
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000,
        ]);
        Xml3176Xml3::create(['ma_lk' => 'B7B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $this->assertContains('XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT', $this->goi('checkMucHuong', $x1));
    }

    // ---- B8: NGAY_TAI_KHAM_KHONG_KHOP_XML14 vs XML14_INFO_ERROR_NGAY_HEN_KL ----

    /** @test */
    public function b8_xml14_co_nhung_ngay_hen_kl_rong_thi_nhuong_xml14_checker()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'B8', 'stt' => 1, 'ngay_tai_kham' => '20260910']);
        Xml3176Xml14::create(['ma_lk' => 'B8', 'ngay_hen_kl' => '']);

        $this->assertNotContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $this->goi('checkNgayTaiKham', $x1));
    }

    // ---- B10: cong kham ngoai tru ----

    private function hoSoKham(string $maLk, array $dong): Xml3176Xml1
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => $maLk, 'stt' => 1, 'ma_loai_kcb' => '01']);
        foreach ($dong as $i => $d) {
            Xml3176Xml3::create([
                'ma_lk' => $maLk, 'stt' => $i + 1, 'ma_nhom' => 13,
                'ma_dich_vu' => $d[0], 'don_gia_bh' => $d[1], 'thanh_tien_bh' => $d[2],
            ]);
        }

        return $x1;
    }

    /** @test */
    public function b10a_hai_dong_cung_ma_kham_chi_bao_trung_khong_bao_lan_2()
    {
        $x1 = $this->hoSoKham('B10A', [['KB1', 100000, 100000], ['KB1', 100000, 100000]]);

        $this->assertSame(['XMLComplete_DUPLICATE_EXAMINATION_SERVICE'], $this->goi('checkExaminationErrors', $x1));
    }

    /** @test */
    public function b10a_ba_dong_cung_ma_kham_chi_bao_trung()
    {
        $x1 = $this->hoSoKham('B10A3', [['KB1', 100000, 100000], ['KB1', 100000, 100000], ['KB1', 100000, 100000]]);

        $this->assertSame(['XMLComplete_DUPLICATE_EXAMINATION_SERVICE'], $this->goi('checkExaminationErrors', $x1));
    }

    /** @test */
    public function b10a_trung_ma_va_them_chuyen_khoa_khac_gia_day_du_van_bao_lan_2()
    {
        $x1 = $this->hoSoKham('B10A2', [['KB1', 100000, 100000], ['KB1', 100000, 100000], ['KB2', 100000, 100000]]);
        $codes = $this->goi('checkExaminationErrors', $x1);

        $this->assertContains('XMLComplete_DUPLICATE_EXAMINATION_SERVICE', $codes);
        $this->assertContains('XMLComplete_EXAMINATION_SECOND_VISIT_RATE', $codes);
        $this->assertNotContains('XMLComplete_EXAMINATION_FEE_EXCEEDS_CAP', $codes);
    }

    /** @test */
    public function b10b_ba_chuyen_khoa_gia_day_du_chi_bao_lan_2()
    {
        $x1 = $this->hoSoKham('B10B', [['KB1', 100000, 100000], ['KB2', 100000, 100000], ['KB3', 100000, 100000]]);

        $this->assertSame(['XMLComplete_EXAMINATION_SECOND_VISIT_RATE'], $this->goi('checkExaminationErrors', $x1));
    }

    /** @test */
    public function b10_vuot_tran_do_nhieu_lan_30_phan_tram_van_bao_vuot_tran()
    {
        $x1 = $this->hoSoKham('B10C', [
            ['KB1', 100000, 100000], ['KB2', 100000, 30000], ['KB3', 100000, 30000],
            ['KB4', 100000, 30000], ['KB5', 100000, 30000],
        ]);

        $this->assertSame(['XMLComplete_EXAMINATION_FEE_EXCEEDS_CAP'], $this->goi('checkExaminationErrors', $x1));
    }

    /** @test */
    public function b10_hai_chuyen_khoa_gia_day_du_van_bao_lan_2()
    {
        $x1 = $this->hoSoKham('B10D', [['KB1', 100000, 100000], ['KB2', 100000, 100000]]);

        $this->assertSame(['XMLComplete_EXAMINATION_SECOND_VISIT_RATE'], $this->goi('checkExaminationErrors', $x1));
    }
}
