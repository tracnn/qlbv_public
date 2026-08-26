<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Jobs\SignTt12Job;
use App\Jobs\SubmitTt12Job;
use App\Services\Tt12\Tt12XepHangKyGui;
use App\Services\Tt12\Tt12HangDoi;

/**
 * Chuoi ky so - gui len cong cho TT12, theo dung khuon CtdtXepHangKyGui da chay that.
 *
 * TRUOC DAY: nut "Ky va gui" moi lan bam CHI lam mot buoc - chua ky thi ky, ky roi moi gui.
 * Ten nut noi mot dang, hanh vi mot neo, va o duong gui hang loat thi thong diep
 * "Da day 30 ho so vao hang doi ky va 0 ho so vao hang doi gui" rat de doc luot thanh
 * "da gui xong 30 ho so".
 *
 * NAY: mot lan bam xep ca chuoi. Kem theo la mot KHOA chong bam trung, vi rut ngan thao tac
 * cung lam rong cua so de hai luot xu ly cua CUNG mot ho so chay chong len nhau.
 */
class Tt12XepHangKyGuiTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
        Queue::fake();

        config([
            'organization.tt12.sign_enabled'   => true,
            'organization.tt12.submit_enabled' => true,
        ]);
    }

    private function tao($ma, array $ghiDe = array())
    {
        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 1,
            'id_danh_sach' => 'Id-' . $ma,
            'checked_at' => '2026-08-26 10:00:00', 'so_loi' => 0,
        ), $ghiDe));
    }

    // -- Chuoi ------------------------------------------------------------------------

    /** @test */
    public function mot_lan_xep_day_job_ky_kem_chuoi_gui()
    {
        $this->tao('A');

        $this->assertTrue(Tt12XepHangKyGui::xep('A'));

        // Job ky duoc day, va no MANG THEO job gui trong chuoi. Hai lan dispatch roi rac se
        // hong: job gui co the chay TRUOC job ky va luon thay is_signed = false.
        Queue::assertPushed(SignTt12Job::class, function ($job) {
            return count($job->chained) === 1;
        });

        Queue::assertNotPushed(SubmitTt12Job::class);
    }

    /** @test */
    public function job_ky_va_job_gui_nam_o_HAI_hang_doi_khac_nhau()
    {
        // Ky hong vi ly do CUC BO (rut USB token), gui hong vi MANG. Chung mot hang doi thi
        // mot lan mang chap keo theo ky lai - thao tac ton thoi gian nhat trong chuoi.
        $this->tao('A');
        Tt12XepHangKyGui::xep('A');

        Queue::assertPushed(SignTt12Job::class, function ($job) {
            return $job->queue === Tt12HangDoi::ky();
        });

        $this->assertNotSame(Tt12HangDoi::ky(), Tt12HangDoi::gui(),
            'ky va gui phai la hai hang doi khac nhau');
        $this->assertNotSame(Tt12HangDoi::kiem(), Tt12HangDoi::ky());
    }

    // -- Khoa chong bam trung ----------------------------------------------------------

    /** @test */
    public function bam_lan_hai_khi_luot_dau_con_chay_thi_bi_tu_choi()
    {
        // Rut tu hai lan bam xuong mot lam RONG cua so dua nhau: truoc day nguoi dung phai
        // bam dung hai lan dung thu tu, gio mot lan la ca chuoi chay - va hai lan bam nhanh
        // se thanh HAI lan POST that len cong.
        $this->tao('A');

        $this->assertTrue(Tt12XepHangKyGui::xep('A'), 'luot dau phai duoc chap nhan');
        $this->assertFalse(Tt12XepHangKyGui::xep('A'), 'luot hai phai bi tu choi');

        Queue::assertPushed(SignTt12Job::class, 1);
    }

    /** @test */
    public function khoa_chi_chan_DUNG_ho_so_do()
    {
        $this->tao('A');
        $this->tao('B');

        $this->assertTrue(Tt12XepHangKyGui::xep('A'));
        $this->assertTrue(Tt12XepHangKyGui::xep('B'), 'khoa cua A khong duoc chan B');
    }

    /** @test */
    public function go_khoa_thi_xep_lai_duoc()
    {
        $this->tao('A');

        Tt12XepHangKyGui::xep('A');
        $this->assertSame(1, Tt12XepHangKyGui::goKhoa('A'));
        $this->assertTrue(Tt12XepHangKyGui::xep('A'));
    }

    /** @test */
    public function khoa_het_han_duoc_don_truoc_khi_chen()
    {
        // Mot tien trinh bi giet giua chuoi de lai khoa mo coi. Khong don thi ho so do khong
        // bao gio gui lai duoc, va khong co dong log nao noi vi sao.
        $this->tao('A');

        DB::table(Tt12XepHangKyGui::BANG_KHOA)->insert(array(
            'ma_ho_so' => 'A', 'het_han_luc' => now()->subMinutes(5),
            'nguon' => 'man_hinh', 'created_at' => now(), 'updated_at' => now(),
        ));

        $this->assertTrue(Tt12XepHangKyGui::xep('A'), 'khoa het han phai duoc don');
    }

    /** @test */
    public function dangXuLy_chi_HOI_chu_khong_dat_khoa()
    {
        // giuKhoa() vua hoi vua dat, nen dung no lam phep tham do se lam chinh nguoi hoi
        // chiem mat khoa - va lan xep hang that ngay sau do bi tu choi boi chinh minh.
        $this->tao('A');

        $this->assertFalse(Tt12XepHangKyGui::dangXuLy('A'));
        $this->assertTrue(Tt12XepHangKyGui::xep('A'), 'dangXuLy() da chiem mat khoa');
        $this->assertTrue(Tt12XepHangKyGui::dangXuLy('A'));
    }

    /** @test */
    public function thoi_han_khoa_lon_hon_ngan_sach_thu_lai_cua_ca_chuoi()
    {
        // SignTt12Job tries x timeout + SubmitTt12Job tries x timeout phai NHO HON thoi han
        // khoa. Khoa ngan hon la mo cua cho lan bam thu hai trong khi chuoi dau con chay.
        $ky  = new SignTt12Job('A');
        $gui = new SubmitTt12Job('A');

        $tong = ($ky->tries * $ky->timeout) + ($gui->tries * $gui->timeout);

        $this->assertGreaterThan($tong, Tt12XepHangKyGui::KHOA_PHUT * 60,
            'thoi han khoa (' . Tt12XepHangKyGui::KHOA_PHUT . ' phut) phai lon hon tong '
            . 'ngan sach thu lai cua ca chuoi (' . $tong . ' giay)');
    }

    // -- Nha khoa ----------------------------------------------------------------------

    /**
     * @test
     */
    public function job_gui_NHA_KHOA_khi_chay_xong()
    {
        // Khong nha thi ho so do bi chan gui lai suot 40 phut, va khong co dong log nao noi
        // vi sao. Trieu chung o man hinh la nut bam bao "dang duoc xu ly o mot luot khac"
        // trong khi khong co luot nao dang chay ca.
        $hoSo = $this->tao('A', array('is_signed' => true, 'duong_dan_da_ky' => 'x.xml'));

        \Illuminate\Support\Facades\Storage::fake('exportTt12');
        \Illuminate\Support\Facades\Storage::disk('exportTt12')->put('x.xml', '<x/>');

        Tt12XepHangKyGui::xep('A');
        $this->assertTrue(Tt12XepHangKyGui::dangXuLy('A'), 'xep() phai dat khoa');

        $job = new SubmitTt12Job('A');
        $job->submitServiceGia = new \Tests\Support\FakeTt12SubmitService();
        $job->handle();

        $this->assertFalse(Tt12XepHangKyGui::dangXuLy('A'), 'chay xong phai nha khoa');
    }

    /** @test */
    public function job_gui_nha_khoa_ca_khi_BO_QUA()
    {
        // Duong bo qua cung phai nha: ho so chua ky thi chuoi den job gui roi dung lai -
        // neu khong nha o day thi khoa nam lai het 40 phut.
        $this->tao('A');   // chua ky

        Tt12XepHangKyGui::xep('A');

        $job = new SubmitTt12Job('A');
        $job->submitServiceGia = new \Tests\Support\FakeTt12SubmitService();
        $job->handle();

        $this->assertFalse(Tt12XepHangKyGui::dangXuLy('A'));
    }

    /** @test */
    public function ca_hai_job_deu_co_failed_de_nha_khoa_khi_het_luot()
    {
        // Khi KY that bai dut diem thi CHUOI DUNG LAI - SubmitTt12Job khong bao gio chay, ma
        // no moi la noi nha khoa o duong binh thuong. Thieu failed() tren job ky thi ho so
        // do bi chan het 40 phut.
        foreach (array(SignTt12Job::class, SubmitTt12Job::class) as $lop) {
            $this->assertTrue(method_exists($lop, 'failed'),
                $lop . ': thieu failed() de nha khoa khi het luot thu lai');
        }
    }
}
