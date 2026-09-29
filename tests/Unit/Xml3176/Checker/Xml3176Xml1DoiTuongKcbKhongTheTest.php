<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Khong co ma the BHYT thi ma doi tuong KCB phai la 9 (nguoi benh khong KCB BHYT).
 *
 * Do tren 41.759 ho so ngay 29/09/2026: 38.331 ho so khong the khai dung ma 9, 342 ho
 * so khong the khai ma khac (3.1: 225, 1.17: 100, 2: 12, 1.1: 4, 3.6: 1). CA 342 deu co
 * T_BHTT = 0, nen quy tac THIEU_THE_BHYT san co (chi no khi T_BHTT > 0) bat duoc 0 ho so.
 *
 * Hai quy tac chia doi dia phan: T_BHTT > 0 thuoc quy tac cu (nang hon - doi quy tra ma
 * khong co the), T_BHTT = 0 thuoc quy tac nay. Moi ho so ra dung MOT dong loi.
 *
 * Nguoi dung chot: KHONG mien ma 2 (cap cuu).
 */
class Xml3176Xml1DoiTuongKcbKhongTheTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML1_DOI_TUONG_KCB_KHONG_THE_SAI_MA';
    const MA_CU = 'XML1_DOI_TUONG_KCB_THIEU_THE_BHYT';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
    }

    private function loi(array $ghiDe)
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '3.1',
            'ma_the_bhyt' => '', 'ma_cskcb' => '01929', 'ma_dkbd' => '',
            'ma_loai_kcb' => '03', 't_bhtt' => 0,
        ], $ghiDe));

        return collect($this->invokePrivate($this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong));
    }

    private function cua($loi, $ma)
    {
        return $loi->where('error_code', $ma)->values();
    }

    /** @test */
    public function khong_the_ma_khai_31_thi_bao_va_neu_ro_ma_da_khai()
    {
        $loi = $this->cua($this->loi([]), self::MA);

        $this->assertCount(1, $loi);
        $this->assertSame('Không có mã thẻ BHYT nhưng mã đối tượng không phải 9', $loi[0]->error_name);
        $this->assertContains('3.1', $loi[0]->description);
        $this->assertContains('phải là 9', $loi[0]->description);
    }

    /** @test */
    public function khong_the_khai_ma_9_la_dung()
    {
        $this->assertCount(0, $this->cua($this->loi(['ma_doituong_kcb' => '9']), self::MA));
    }

    /** @test */
    public function co_the_thi_khong_ap_dung()
    {
        $this->assertCount(0, $this->cua($this->loi(['ma_the_bhyt' => 'HC4010112345678']), self::MA));
    }

    /** @test */
    public function cap_cuu_khong_duoc_mien()
    {
        // Nguoi dung chot ngay 29/09/2026: khong the thi phai la 9, khong ngoai le nao.
        $this->assertCount(1, $this->cua($this->loi(['ma_doituong_kcb' => '2']), self::MA));
    }

    /** @test */
    public function doi_quy_tra_thi_nhuong_quy_tac_cu_moi_ho_so_mot_dong_loi()
    {
        $loi = $this->loi(['t_bhtt' => 500000]);

        $this->assertCount(1, $this->cua($loi, self::MA_CU), 'Quy tac cu van phai no');
        $this->assertCount(0, $this->cua($loi, self::MA), 'Khong duoc ra hai dong loi cho cung mot ho so');
    }

    /** @test */
    public function t_bhtt_rong_coi_nhu_khong_doi_quy_tra()
    {
        $this->assertCount(1, $this->cua($this->loi(['t_bhtt' => null]), self::MA));
    }

    /** @test */
    public function ma_doi_tuong_rong_thi_im_lang()
    {
        // Da co ADMIN_INFO_ERROR_MA_DOITUONG_KCB lo; khong suy dien tu du lieu thieu.
        $this->assertCount(0, $this->cua($this->loi(['ma_doituong_kcb' => '']), self::MA));
    }

    /** @test */
    public function ma_the_chi_gom_khoang_trang_la_khong_co_the()
    {
        // Truoc day !empty('   ') = true nen chuoi trang bi coi la CO the.
        $this->assertCount(1, $this->cua($this->loi(['ma_the_bhyt' => '   ']), self::MA));
    }
}
