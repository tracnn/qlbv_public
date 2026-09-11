<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml1DoiTuongKcbTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
    }

    private function codes(array $ghiDe): array
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ma_dkbd' => '36001',
            'ma_loai_kcb' => '03',
        ], $ghiDe));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ));
    }

    /** @test */
    public function ma_rong_thi_im_lang()
    {
        // Da co ADMIN_INFO_ERROR_MA_DOITUONG_KCB lo truong hop rong.
        $this->assertSame([], $this->codes(['ma_doituong_kcb' => null]));
        $this->assertSame([], $this->codes(['ma_doituong_kcb' => '']));
    }

    /** @test */
    public function ma_ngoai_danh_muc()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '1.9']));
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '4']));
        // '3' khong phai ma hop le, chi 3.1/3.2/3.3/3.6 moi la
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '3']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '1.5']));
    }

    /** @test */
    public function ma_13_thieu_ma_noi_di()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => null]));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001']));

        // Ma khac khong doi MA_NOI_DI
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_noi_di' => null]));
    }

    /** @test */
    public function ma_tu_den_khong_duoc_co_ma_noi_di()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_noi_di' => '36001']));

        $this->assertContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_noi_di' => '36001']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_noi_di' => null]));
    }

    /** @test */
    public function khong_co_the_bhyt_ma_van_de_nghi_quy_thanh_toan()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => 500000]));
    }

    /** @test */
    public function khong_co_the_nhung_chua_de_nghi_quy_tra_thi_im_lang()
    {
        // Cap cuu la ngoai le da biet: chuan cho phep tra cuu the truoc khi ra vien.
        // Chi mau thuan that khi doi quy tra ma khong co the.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => null]));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => 0]));
    }

    /** @test */
    public function ma_9_khong_KCB_BHYT_thi_khong_duoc_co_the()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => 'DN4010112345678']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => '']));

        // Ma 9 khong the dong thoi bi bao THIEU_THE_BHYT
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => '', 't_bhtt' => 500000]));
    }

    /** @test */
    public function dung_noi_dang_ky_ban_dau_ma_khai_sai_ma()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.2', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));
    }

    /** @test */
    public function ma_dkbd_nhieu_ma_ngan_boi_dau_cham_phay()
    {
        // Chuan cho phep MA_DKBD chua nhieu ma khi nguoi benh doi the giua dot.
        // So chuoi tho se bo sot ca nay.
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_dkbd' => '36001;01929', 'ma_cskcb' => '01929']));
    }

    /** @test */
    public function ma_31_ngoai_tru_thi_khong_duoc_huong()
    {
        // Danh muc: ma 3.1 huong 40% noi tru, 0% ngoai tru.
        $this->assertContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '01', 't_bhtt' => 300000]));

        // Noi tru thi quy tac nay im lang - da co checkMucHuong lo muc 40%.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '03', 't_bhtt' => 300000]));

        // Ngoai tru ma khong de nghi quy tra thi khong sai.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '01', 't_bhtt' => 0]));
    }

    /** @test */
    public function ho_so_dung_hoan_toan_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes([
            'ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001',
            'ma_dkbd' => '36001', 'ma_cskcb' => '01929', 't_bhtt' => 500000,
        ]));
    }
}
