<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml14;
use App\Models\BHYT\Xml3176Xml9;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteLienBangTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152906_create_xml3176_xml9s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    private function taiKham(Xml3176Xml1 $x1): array
    {
        return $this->errorCodes($this->invokePrivate($this->checker(), 'checkNgayTaiKham', $x1));
    }

    private function canNang(Xml3176Xml1 $x1): array
    {
        return $this->errorCodes($this->invokePrivate($this->checker(), 'checkCanNangCon', $x1));
    }

    /** @test */
    public function ngay_tai_kham_rong_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'A', 'stt' => 1, 'ngay_tai_kham' => null]);
        $this->assertSame([], $this->taiKham($x1));
    }

    /** @test */
    public function ngay_tai_kham_sai_dinh_dang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1, 'ngay_tai_kham' => '2026091']);
        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_SAI_DINH_DANG', $this->taiKham($x1));

        $x1 = Xml3176Xml1::create(['ma_lk' => 'B2', 'stt' => 1, 'ngay_tai_kham' => '20261332']);
        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_SAI_DINH_DANG', $this->taiKham($x1));
    }

    /** @test */
    public function phan_tu_sai_dinh_dang_khong_bao_them_loi_khong_khop()
    {
        // Mot gia tri chi sinh mot loi: sai dinh dang thi khong xet khop XML14 nua.
        $x1 = Xml3176Xml1::create(['ma_lk' => 'B3', 'stt' => 1, 'ngay_tai_kham' => '2026091']);
        $codes = $this->taiKham($x1);

        $this->assertNotContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $codes);
    }

    /** @test */
    public function co_ngay_tai_kham_nhung_khong_co_xml14()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1, 'ngay_tai_kham' => '20260910']);
        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $this->taiKham($x1));
    }

    /** @test */
    public function xml14_co_nhung_lech_ngay()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'D', 'stt' => 1, 'ngay_tai_kham' => '20260910']);
        Xml3176Xml14::create(['ma_lk' => 'D', 'ngay_hen_kl' => '20260913']);

        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $this->taiKham($x1));
    }

    /** @test */
    public function khop_du_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'E', 'stt' => 1, 'ngay_tai_kham' => '20260910']);
        Xml3176Xml14::create(['ma_lk' => 'E', 'ngay_hen_kl' => '20260910']);

        $this->assertSame([], $this->taiKham($x1));
    }

    /** @test */
    public function nhieu_ngay_va_giay_hen_nam_trong_so_do_thi_im_lang()
    {
        // xml3176_xml14s.ma_lk la UNIQUE - moi ho so chi co MOT dong XML14. Nen huong so
        // sanh la: ngay tren giay hen phai nam trong tap NGAY_TAI_KHAM, khong phai nguoc
        // lai. Doi chieu nguoc lai se bao loi do chinh luoc do, khong do du lieu sai.
        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'F', 'stt' => 1, 'ngay_tai_kham' => '20260910;20260913',
        ]);
        Xml3176Xml14::create(['ma_lk' => 'F', 'ngay_hen_kl' => '20260913']);

        $this->assertSame([], $this->taiKham($x1));
    }

    /** @test */
    public function giay_hen_ghi_ngay_khong_co_trong_ngay_tai_kham()
    {
        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'G', 'stt' => 1, 'ngay_tai_kham' => '20260910;20260913',
        ]);
        Xml3176Xml14::create(['ma_lk' => 'G', 'ngay_hen_kl' => '20260920']);

        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $this->taiKham($x1));
    }

    /** @test */
    public function xml14_co_nhung_ngay_hen_kl_rong_thi_van_bao()
    {
        // NGAY_HEN_KL rong khong duoc im lang: vua mu truoc ho so thieu du lieu, vua la
        // duong ne (tao mot dong XML14 rong la tat duoc quy tac).
        $x1 = Xml3176Xml1::create(['ma_lk' => 'G2', 'stt' => 1, 'ngay_tai_kham' => '20260910']);
        Xml3176Xml14::create(['ma_lk' => 'G2', 'ngay_hen_kl' => '']);

        $this->assertContains('XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14', $this->taiKham($x1));
    }

    /** @test */
    public function can_nang_con_ma_khong_co_giay_chung_sinh()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'H', 'stt' => 1, 'can_nang_con' => '3200']);
        $this->assertContains('XMLComplete_CAN_NANG_CON_THIEU_XML9', $this->canNang($x1));
    }

    /** @test */
    public function can_nang_con_va_co_giay_chung_sinh_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'I', 'stt' => 1, 'can_nang_con' => '3200;2800']);
        Xml3176Xml9::create(['ma_lk' => 'I']);

        $this->assertSame([], $this->canNang($x1));
    }

    /** @test */
    public function khong_co_can_nang_con_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'J', 'stt' => 1, 'can_nang_con' => null]);
        $this->assertSame([], $this->canNang($x1));
    }
}
