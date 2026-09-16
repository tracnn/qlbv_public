<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Quy tac bo sung theo tep "quy tac doi tuong.xlsx": ma 1.1, 3.6, 1.17.
 * Spec: docs/superpowers/specs/2026-09-15-xml3176-quy-tac-doi-tuong-bo-sung-design.md muc 7.
 */
class Xml3176Xml1DoiTuongKcbBoSungTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php',
        ]);
    }

    private function codes(array $ghiDe): array
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ma_dkbd' => '01929',
            'ma_loai_kcb' => '03',
            'ma_khuvuc' => 'K1',
            'ngay_sinh' => '198001010000',
            'ngay_vao' => '202609150800',
        ], $ghiDe));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ));
    }

    private function napPl1()
    {
        DB::table('benh_pl1_cap_chuyen_sau')->insert([
            ['stt' => 22, 'ma_icd' => 'C50', 'loai' => 'bao_gom', 'tuoi_duoi' => 18, 'is_active' => 1],
            ['stt' => 62, 'ma_icd' => 'Z94', 'loai' => 'bao_gom', 'tuoi_duoi' => null, 'is_active' => 1],
            ['stt' => 99, 'ma_icd' => 'G44', 'loai' => 'bao_gom', 'tuoi_duoi' => null, 'is_active' => 0],
        ]);
    }

    // ─── 1.1 ────────────────────────────────────────────────────────────────

    /** @test */
    public function ma_11_dkbd_bang_cskcb_thi_sach()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1']));
    }

    /** @test */
    public function ma_11_co_mot_ma_dkbd_khac_cskcb_thi_loi()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '36001']));

        // Nguoi dung chot nghia CHAT: doi the giua dot sang noi DKBD khac cung bao.
        $this->assertContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929;37470']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929; 01929']));
    }

    /** @test */
    public function ma_11_thieu_can_cu_thi_im_lang()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '36001', 'ma_cskcb' => '']));
    }

    /** @test */
    public function ma_khac_11_khong_kiem_dkbd_bang_cskcb()
    {
        foreach (['1.2', '1.3', '1.5', '2'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_dkbd' => '36001']), "ma $ma");
        }
    }

    // ─── 3.6 ────────────────────────────────────────────────────────────────

    /** @test */
    public function ma_36_thieu_ma_khuvuc_thi_loi()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => '']));
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => null]));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => 'K2']));
    }

    /** @test */
    public function ma_khac_36_khong_doi_ma_khuvuc()
    {
        foreach (['1.1', '3.1', '3.2', '1.17'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_khuvuc' => '']), "ma $ma");
        }
    }

    // ─── 1.17 ───────────────────────────────────────────────────────────────

    /** @test */
    public function ma_117_danh_muc_rong_thi_im_lang()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'G44.0']));
    }

    /** @test */
    public function ma_117_benh_trong_danh_muc_thi_sach()
    {
        $this->napPl1();

        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'Z94.0']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'C50.9', 'ngay_sinh' => '201501010000']));
    }

    /** @test */
    public function ma_117_benh_ngoai_danh_muc_hoac_dong_da_tat_thi_loi()
    {
        $this->napPl1();

        // G44 co trong bang nhung is_active = 0 - khong duoc tinh.
        $this->assertContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'G44.0']));
    }

    /** @test */
    public function ma_117_sai_tuoi_thi_loi_va_mo_ta_neu_ro_dong()
    {
        $this->napPl1();

        $dong = new Xml3176Xml1([
            'ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'C50.9',
            'ngay_sinh' => '198001010000', 'ngay_vao' => '202609150800',
            'ma_cskcb' => '01929', 'ma_dkbd' => '01929', 'ma_the_bhyt' => 'DN4010112345678',
        ]);

        $loi = collect($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ))->firstWhere('error_code', 'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1');

        $this->assertNotNull($loi);
        $this->assertContains('C50.9', $loi->description);
        $this->assertContains('dòng 22', $loi->description);
        $this->assertContains('46 tuổi', $loi->description);
    }

    /** @test */
    public function ma_117_thieu_ma_benh_chinh_thi_im_lang()
    {
        $this->napPl1();

        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => '']));
    }

    /** @test */
    public function ma_khac_117_khong_kiem_pl1()
    {
        $this->napPl1();

        foreach (['1.16', '1.1', '3.6'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_benh_chinh' => 'G44.0']), "ma $ma");
        }
    }
}
