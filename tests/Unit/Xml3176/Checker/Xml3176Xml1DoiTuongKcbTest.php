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
    public function ma_9_khong_bi_bao_thieu_the_bhyt()
    {
        // Quy tac DOI_TUONG_KCB_KHONG_BHYT_CO_THE da bo han: ho so ma 9 bi chan tu diem
        // phat job (xml3176.ma_doituong_kcb_khong_kiem) nen checker nay khong bao gio
        // chay tren chung - dong danh muc cua no la dong rac. Chi con giu lai assert nay:
        // ma 9 khong the dong thoi bi bao THIEU_THE_BHYT.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => '', 't_bhtt' => 500000]));
    }

    /** @test */
    public function dung_noi_dang_ky_ban_dau_ma_khai_sai_ma()
    {
        // Ma 1.3 co 'can_noi_di' -> khang dinh den tu noi khac -> dung DKBD ma khai 1.3
        // la mau thuan that.
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.2', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        // Ma 2 (cap cuu) khong co 'tu_den' hoac 'can_noi_di' -> khong khang dinh den tu
        // noi khac -> nguoi dang ky ban dau tai chinh co so nay vao cap cuu la hop le,
        // khong phai vi pham. Do lai tren du lieu that: day tung la 1 trong 2 "vi pham"
        // spec bao cao, thuc ra la bao oan.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));
    }

    /** @test */
    public function ma_dkbd_nhieu_ma_ngan_boi_dau_cham_phay()
    {
        // Chuan cho phep MA_DKBD chua nhieu ma khi nguoi benh doi the giua dot.
        // So chuoi tho se bo sot ca nay. Dung ma 1.3 (co 'can_noi_di') de vi pham that.
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001', 'ma_dkbd' => '36001;01929', 'ma_cskcb' => '01929']));
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

        // MA_LOAI_KCB rong thi khong co can cu ket luan "ngoai tru" - im lang, khong
        // duoc suy dien tu du lieu vang.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '', 't_bhtt' => 300000]));
    }

    /** @test */
    public function ma_13_va_15_phai_co_so_giay_chuyen_tuyen()
    {
        // Chuan du lieu dau ra (QD 130 sua doi theo QD 4750), truong 38 GIAY_CHUYEN_TUYEN:
        // "Ghi so giay chuyen tuyen cua co so KBCB/So giay chuyen co so KBCB noi chuyen
        // nguoi benh di (trong truong hop nguoi benh co giay chuyen tuyen) HOAC so giay
        // hen kham lai (neu co)."
        //
        // Ma 1.3 la "den KCB co phieu chuyen co so KCB" va ma 1.5 la "den KCB theo phieu
        // hen kham lai": voi hai ma nay to phieu TON TAI theo dinh nghia cua chinh ma do,
        // nen so phieu phai duoc ghi. Cum "(neu co)" noi ve truong hop chung, khong mien
        // tru hai ma nay.
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            $this->codes(['ma_doituong_kcb' => '1.5', 'giay_chuyen_tuyen' => null]));

        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            $this->codes(['ma_doituong_kcb' => '1.5', 'giay_chuyen_tuyen' => '   ']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            $this->codes(['ma_doituong_kcb' => '1.5', 'giay_chuyen_tuyen' => '127/2025/GCT']));

        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001', 'giay_chuyen_tuyen' => '']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001', 'giay_chuyen_tuyen' => '26097500']));
    }

    /** @test */
    public function ma_khac_khong_bi_doi_giay_chuyen_tuyen()
    {
        // Chi hai ma khai san to phieu moi bi doi. Cac ma tu den / dung DKBD khong co
        // phieu nao ca - doi so phieu o day la bao oan.
        foreach (['1.1', '1.4', '1.11', '2', '3.1'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
                $this->codes(['ma_doituong_kcb' => $ma, 'giay_chuyen_tuyen' => null]),
                'ma ' . $ma . ' khong duoc doi GIAY_CHUYEN_TUYEN');
        }
    }

    /** @test */
    public function ho_so_dung_hoan_toan_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes([
            'ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001',
            'ma_dkbd' => '36001', 'ma_cskcb' => '01929', 't_bhtt' => 500000,
            'giay_chuyen_tuyen' => '26097500',
        ]));
    }
}
