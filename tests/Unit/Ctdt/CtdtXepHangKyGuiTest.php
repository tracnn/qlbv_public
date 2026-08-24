<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Jobs\SignCtdtJob;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtXepHangKyGui;

/**
 * Man hinh va lenh Console phai xep hang bang DUNG MOT BAN. Hai ban se lech nhau - dung
 * dieu da xay ra that voi XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao.
 */
class CtdtXepHangKyGuiTest extends TestCase
{
    // Khoa nam o bang ctdt_khoa_xu_ly (unique index) chu khong o Cache nua, nen test phai
    // co bang that. Xem chu thich o migration create_ctdt_khoa_xu_ly_table.
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
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
        // giuKhoa() vua hoi vua dat. Dung no lam phep tham do se lam chinh nguoi hoi
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
    public function khoa_het_han_dung_30_phut_sau()
    {
        // Test HANH VI chu khong soi hang so: doc thang het_han_luc da ghi vao bang. Nham
        // don vi (addSeconds thay addMinutes) van lam assertSame(30, KHOA_PHUT) xanh, trong
        // khi khoa that chi song 30 giay - va lan bam thu hai ngay sau do se xep hang duoc.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        $khoa = DB::table('ctdt_khoa_xu_ly')->where('ma_ho_so', 'YT001')->first();

        $this->assertNotNull($khoa, 'Phai co dong khoa trong bang');
        $this->assertSame(
            30,
            (int) round(Carbon::parse($khoa->het_han_luc)->diffInSeconds(Carbon::now()) / 60),
            'Khoa phai het han 30 PHUT sau, khong phai 30 giay hay 30 gio'
        );
    }

    /** @test */
    public function khoa_HET_HAN_khong_chan_luot_moi()
    {
        // Mot tien trinh bi giet giua chuoi ky-gui de lai khoa mo coi. Khong don thi ho so
        // do khong bao gio gui duoc nua, va trieu chung la mot dong "dang xu ly" vinh vien
        // ma khong co dong log nao.
        Bus::fake();

        DB::table('ctdt_khoa_xu_ly')->insert([
            'ma_ho_so'    => 'YT001',
            'het_han_luc' => Carbon::now()->subMinute(),
            'nguon'       => 'man_hinh',
            'created_at'  => Carbon::now()->subHour(),
            'updated_at'  => Carbon::now()->subHour(),
        ]);

        $this->assertFalse(CtdtXepHangKyGui::dangXuLy('YT001'),
            'Khoa da het han thi khong con tinh la dang xu ly');
        $this->assertTrue(CtdtXepHangKyGui::xep('YT001'),
            'Khoa het han phai duoc don, khong duoc chan luot moi');
    }

    /** @test */
    public function goKhoa_mo_lai_duong_gui_ngay()
    {
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');
        $this->assertFalse(CtdtXepHangKyGui::xep('YT001'));

        CtdtXepHangKyGui::goKhoa('YT001');

        $this->assertTrue(CtdtXepHangKyGui::xep('YT001'),
            'Sau goKhoa() phai xep hang lai duoc ngay, khong phai cho het 30 phut');
    }

    /** @test */
    public function loi_CSDL_KHAC_trung_khoa_phai_nem_ra_chu_khong_hoa_thanh_dang_xu_ly()
    {
        // Bat QueryException chung chung se bien MOI loi CSDL - thieu bang, mat ket noi -
        // thanh "ho so dang xu ly": nut bam bao mot cau vo hai, khong dong log nao, va khong
        // ho so nao gui duoc nua. Chi SQLSTATE 23000 moi la "da co nguoi giu".
        Schema::drop('ctdt_khoa_xu_ly');

        $this->expectException(\Illuminate\Database\QueryException::class);

        CtdtXepHangKyGui::giuKhoa('YT001');
    }
}
