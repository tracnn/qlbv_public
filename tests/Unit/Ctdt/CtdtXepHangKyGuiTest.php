<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SignCtdtJob;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtXepHangKyGui;

/**
 * Man hinh va lenh Console phai xep hang bang DUNG MOT BAN. Hai ban se lech nhau - dung
 * dieu da xay ra that voi XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao.
 */
class CtdtXepHangKyGuiTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function xep_hang_lan_dau_thi_thanh_cong()
    {
        Bus::fake();

        $this->assertTrue(CtdtXepHangKyGui::xep('YT001', 'bsnguyen'));

        Bus::assertDispatched(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_dang_xu_ly_thi_bi_tu_choi()
    {
        // Bam hai lan = hai lan POST that len cong. PL02 khong co ma giao dich phia client
        // nen cong khong khu trung duoc.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        $this->assertFalse(CtdtXepHangKyGui::xep('YT001'),
            'Ho so dang xu ly phai bi tu choi, khong duoc xep hang lan hai');
    }

    /** @test */
    public function khoa_theo_TUNG_ma_ho_so_chu_khong_phai_mot_khoa_chung()
    {
        // Mot khoa chung se khoa ca he thong lai chi vi mot ho so dang chay - lenh Console
        // xu 200 ho so mot luot se chi xep duoc dung mot cai.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        $this->assertTrue(CtdtXepHangKyGui::xep('YT002'),
            'Ho so KHAC phai xep hang duoc binh thuong');
    }

    /** @test */
    public function dangXuLy_chi_hoi_chu_KHONG_dat_khoa()
    {
        // Cache::add() vua hoi vua dat. Dung no lam phep tham do se lam chinh nguoi hoi
        // chiem mat khoa, va lan xep hang that ngay sau do bi tu choi boi chinh minh.
        Bus::fake();

        $this->assertFalse(CtdtXepHangKyGui::dangXuLy('YT001'));
        $this->assertTrue(CtdtXepHangKyGui::xep('YT001'),
            'dangXuLy() khong duoc chiem khoa');
    }

    /** @test */
    public function xep_dung_hai_hang_doi_rieng()
    {
        // Ky hong vi ly do CUC BO (USB token bi rut), gui hong vi MANG. Gop chung thi mot
        // lan mang chap keo theo ba lan ky lai.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        Bus::assertDispatched(SignCtdtJob::class, function ($job) {
            return $job->queue === CtdtHangDoi::ky();
        });
    }

    /** @test */
    public function khoa_tinh_bang_PHUT_chu_khong_phai_giay()
    {
        // Cache::add($khoa, $giaTri, $phut) trong Laravel 5.5 nhan PHUT. Truyen giay vao se
        // khoa ho so lai 30 tieng thay vi 30 phut.
        $this->assertSame(30, CtdtXepHangKyGui::KHOA_PHUT);
    }
}
