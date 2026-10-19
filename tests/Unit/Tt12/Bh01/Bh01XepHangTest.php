<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Jobs\SignBh01Job;
use App\Jobs\SubmitBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01LichSuGui;
use App\Services\Tt12\Bh01\Bh01XepHang;
use App\Services\Tt12\Tt12HangDoi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01XepHangTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
        Queue::fake();
    }

    private function ban($soBan, array $ghiDe = array())
    {
        return Bh01Ban::create(array_merge(array(
            'ma_ban' => sprintf('BH01_01929_202609_%02d', $soBan), 'ma_cskcb' => '01929',
            'nam_qt' => '2026', 'thang_qt' => '09', 'so_ban' => $soBan, 'so_dong' => 1,
            'id_ds_chitiet' => 'Id-' . $soBan, 'trang_thai_ky' => Bh01Ban::KY_XONG,
        ), $ghiDe));
    }

    /** @test */
    public function bam_gui_hai_lan_lien_chi_xep_mot()
    {
        $this->ban(1);

        $this->assertTrue(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));
        $this->assertFalse(Bh01XepHang::xepGui('BH01_01929_202609_01', 'b'));

        Queue::assertPushed(SubmitBh01Job::class, 1);
        Queue::assertPushedOn(Tt12HangDoi::gui(), SubmitBh01Job::class);

        // Job mang DUNG gia tri khoa da ghi: no dung gia tri nay de biet khoa con la cua minh.
        $khoa = DB::table('bh01_ban')->where('so_ban', 1)->value('dang_xu_ly_tu');
        $this->assertNotEmpty($khoa);
        Queue::assertPushed(SubmitBh01Job::class, function ($job) use ($khoa) {
            return $job->khoa() === (string) $khoa;
        });
    }

    /** @test */
    public function khoa_qua_han_thi_xep_lai_duoc()
    {
        $this->ban(1, array('dang_xu_ly_tu' => now()->subMinutes(Bh01XepHang::KHOA_PHUT + 1)));

        $this->assertTrue(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));

        Queue::assertPushed(SubmitBh01Job::class, 1);
    }

    /** @test */
    public function khoa_con_han_thi_tu_choi()
    {
        $this->ban(1, array('dang_xu_ly_tu' => now()->subMinutes(Bh01XepHang::KHOA_PHUT - 1)));

        $this->assertFalse(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));

        Queue::assertNotPushed(SubmitBh01Job::class);
    }

    /** @test */
    public function nha_khoa_thi_xep_lai_duoc()
    {
        $this->ban(1);

        $this->assertTrue(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));
        $khoa = DB::table('bh01_ban')->where('so_ban', 1)->value('dang_xu_ly_tu');

        $this->assertSame(0, Bh01XepHang::nhaKhoa('BH01_01929_202609_01', '2000-01-01 00:00:00'),
            'Khoa khac gia tri thi khong nha');
        $this->assertSame(0, Bh01XepHang::nhaKhoa('BH01_01929_202609_01', null),
            'Khong co khoa thi khong nha - where(cot, null) se thanh whereNull');
        $this->assertFalse(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));

        $this->assertSame(1, Bh01XepHang::nhaKhoa('BH01_01929_202609_01', $khoa));
        $this->assertTrue(Bh01XepHang::xepGui('BH01_01929_202609_01', 'a'));

        Queue::assertPushed(SubmitBh01Job::class, 2);
    }

    /** @test */
    public function ban_khong_ton_tai_thi_khong_xep()
    {
        $this->assertFalse(Bh01XepHang::xepGui('BH01_KHONG_CO', 'a'));

        Queue::assertNotPushed(SubmitBh01Job::class);
    }

    /** @test */
    public function khoa_dai_hon_ngan_sach_thu_lai_cua_job_gui()
    {
        $job = new SubmitBh01Job('x');

        $this->assertGreaterThanOrEqual($job->tries * $job->timeout, Bh01XepHang::KHOA_PHUT * 60);
    }

    /** @test */
    public function xep_ky_dat_lai_cho_ky_va_day_vao_hang_doi_ky()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_LOI, 'signed_error' => 'cu'));

        $this->assertTrue(Bh01XepHang::xepKy('BH01_01929_202609_01'));

        $ban = Bh01Ban::where('so_ban', 1)->first();
        $this->assertSame(Bh01Ban::KY_CHO, $ban->trang_thai_ky);
        $this->assertNull($ban->signed_error);
        Queue::assertPushedOn(Tt12HangDoi::ky(), SignBh01Job::class);
    }

    /** @test */
    public function xep_ky_tu_choi_ban_da_gui_hoac_dang_gui()
    {
        $this->ban(1, array('submitted_at' => '2026-10-01 10:00:00', 'ma_ket_qua' => '500'));
        $this->ban(2, array('trang_thai_ky' => Bh01Ban::KY_LOI, 'dang_xu_ly_tu' => now()));
        // Khoa da qua han van chan: khoa con nam do nghia la mot luot gui da bat dau ma chua ai
        // biet ket qua.
        $this->ban(3, array('trang_thai_ky' => Bh01Ban::KY_LOI,
            'dang_xu_ly_tu' => now()->subMinutes(Bh01XepHang::KHOA_PHUT + 5)));

        $this->assertFalse(Bh01XepHang::xepKy('BH01_01929_202609_01'));
        $this->assertFalse(Bh01XepHang::xepKy('BH01_01929_202609_02'));
        $this->assertFalse(Bh01XepHang::xepKy('BH01_01929_202609_03'));

        Queue::assertNotPushed(SignBh01Job::class);
        $this->assertSame(Bh01Ban::KY_XONG, Bh01Ban::where('so_ban', 1)->value('trang_thai_ky'));
        $this->assertSame(Bh01Ban::KY_LOI, Bh01Ban::where('so_ban', 2)->value('trang_thai_ky'));
    }

    /** Dat lai updated_at sau khi tao (Eloquent tu ghi now()). */
    private function datCapNhatLuc($soBan, $luc)
    {
        DB::table('bh01_ban')->where('so_ban', $soBan)->update(array('updated_at' => $luc));
    }

    /** @test */
    public function ky_lai_bi_tu_choi_khi_da_co_lich_su_gui()
    {
        // Da tung gui (ke ca gui hong, loi mang khong ro ket qua): tep da ky la dau vet doi soat.
        $ban = $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_LOI));
        Bh01LichSuGui::create(array('ban_id' => $ban->id, 'gui_luc' => now(), 'loi' => 'Lỗi mạng/không rõ kết quả: x'));

        $this->assertFalse(Bh01XepHang::coTheKyLai(Bh01Ban::find($ban->id)));
        $this->assertFalse(Bh01XepHang::xepKy($ban->ma_ban));

        Queue::assertNotPushed(SignBh01Job::class);
        $this->assertSame(Bh01Ban::KY_LOI, Bh01Ban::find($ban->id)->trang_thai_ky);
    }

    /** @test */
    public function ky_lai_bi_tu_choi_ban_da_ky()
    {
        $ban = $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_XONG));
        $this->datCapNhatLuc(1, now()->subHour());

        $this->assertFalse(Bh01XepHang::coTheKyLai(Bh01Ban::find($ban->id)));
        $this->assertFalse(Bh01XepHang::xepKy($ban->ma_ban));

        Queue::assertNotPushed(SignBh01Job::class);
        $this->assertSame(Bh01Ban::KY_XONG, Bh01Ban::find($ban->id)->trang_thai_ky);
    }

    /** @test */
    public function ky_lai_duoc_khi_cho_ky_qua_10_phut()
    {
        // Job ky bi mat (hang doi bi xoa, worker chet truoc khi failed() chay): ban nam cho_ky mai.
        $ban = $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));
        $this->datCapNhatLuc(1, now()->subMinutes(Bh01XepHang::KY_LAI_SAU_PHUT + 1));

        $this->assertTrue(Bh01XepHang::coTheKyLai(Bh01Ban::find($ban->id)));
        $this->assertTrue(Bh01XepHang::xepKy($ban->ma_ban));

        Queue::assertPushed(SignBh01Job::class, 1);
        // xepKy dat lai dong ho: bam lien lan nua thi tu choi.
        $this->assertFalse(Bh01XepHang::coTheKyLai(Bh01Ban::find($ban->id)));
        $this->assertFalse(Bh01XepHang::xepKy($ban->ma_ban));
        Queue::assertPushed(SignBh01Job::class, 1);
    }

    /** @test */
    public function ky_lai_bi_tu_choi_khi_cho_ky_moi()
    {
        // Job ky co the con trong hang doi: xep them la hai lan ky chong tren cung mot tep.
        $ban = $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));
        $this->datCapNhatLuc(1, now()->subMinutes(Bh01XepHang::KY_LAI_SAU_PHUT - 1));

        $this->assertFalse(Bh01XepHang::coTheKyLai(Bh01Ban::find($ban->id)));
        $this->assertFalse(Bh01XepHang::xepKy($ban->ma_ban));

        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function ky_lai_khong_som_hon_ngan_sach_thu_lai_cua_job_ky()
    {
        $job = new SignBh01Job('x');

        $this->assertGreaterThanOrEqual($job->tries * $job->timeout, Bh01XepHang::KY_LAI_SAU_PHUT * 60);
    }

    /** @test */
    public function xep_ky_ban_moi_chi_cho_ban_cho_ky_chua_gui()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));
        $this->ban(2, array('trang_thai_ky' => Bh01Ban::KY_XONG));
        $this->ban(3, array('trang_thai_ky' => Bh01Ban::KY_CHO, 'submitted_at' => now()));

        $this->assertTrue(Bh01XepHang::xepKyBanMoi('BH01_01929_202609_01'));
        $this->assertFalse(Bh01XepHang::xepKyBanMoi('BH01_01929_202609_02'));
        $this->assertFalse(Bh01XepHang::xepKyBanMoi('BH01_01929_202609_03'));

        Queue::assertPushed(SignBh01Job::class, 1);
    }

    /** @test */
    public function da_duoc_tiep_nhan_tra_ban_200_moi_nhat()
    {
        $this->ban(1, array('ma_ket_qua' => '200', 'ma_gd' => 'GD1'));
        $this->ban(2, array('ma_ket_qua' => '200', 'ma_gd' => 'GD2'));
        $this->ban(3, array('ma_ket_qua' => '500'));
        $this->ban(4);
        // Ky khac, co so khac: khong duoc lan vao.
        Bh01Ban::create(array('ma_ban' => 'BH01_37470_202609_05', 'ma_cskcb' => '37470', 'nam_qt' => '2026',
            'thang_qt' => '09', 'so_ban' => 5, 'ma_ket_qua' => '200'));
        Bh01Ban::create(array('ma_ban' => 'BH01_01929_202610_06', 'ma_cskcb' => '01929', 'nam_qt' => '2026',
            'thang_qt' => '10', 'so_ban' => 6, 'ma_ket_qua' => '200'));

        $ban = Bh01XepHang::daDuocTiepNhan('01929', '2026', '09');

        $this->assertNotNull($ban);
        $this->assertSame(2, $ban->so_ban);
        $this->assertSame('GD2', $ban->ma_gd);
        $this->assertSame(2, Bh01XepHang::daDuocTiepNhan('01929', 2026, 9)->so_ban, 'Thang khong co so 0 dau');
        $this->assertNull(Bh01XepHang::daDuocTiepNhan('01929', '2026', '11'));
    }
}
