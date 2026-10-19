<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Jobs\SubmitBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01LichSuGui;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DungBangBh01Sqlite;
use Tests\Support\FakeTt12SubmitService;
use Tests\TestCase;

class SubmitBh01JobTest extends TestCase
{
    use DungBangBh01Sqlite;

    const MA_BAN = 'BH01_37470_202609_02';
    const TEP = 'BH01/202609/BH01_37470_202609_02.xml';
    /** Gia tri khoa ma Bh01XepHang::xepGui da ghi vao dang_xu_ly_tu va trao cho job */
    const KHOA = '2026-10-19 08:00:00';
    const NOI_DUNG = "<HSTH01BH><DS_CHITIET Id=\"Id-x\"/><CHUKYDONVI><Signature/></CHUKYDONVI></HSTH01BH>";

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();

        Storage::fake('exportTt12');

        config(['organization.tt12.submit_enabled' => true]);
    }

    private function ban(array $ghiDe = array())
    {
        Storage::disk('exportTt12')->put(self::TEP, self::NOI_DUNG);

        return Bh01Ban::create(array_merge(array(
            'ma_ban' => self::MA_BAN, 'ma_cskcb' => '37470', 'nam_qt' => '2026', 'thang_qt' => '09',
            'so_ban' => 2, 'so_dong' => 1, 'id_ds_chitiet' => 'Id-x',
            'trang_thai_ky' => Bh01Ban::KY_XONG, 'signed_at' => '2026-10-01 10:00:00',
            'duong_dan_da_ky' => self::TEP, 'sha256_tep' => hash('sha256', self::NOI_DUNG),
            'dang_xu_ly_tu' => self::KHOA,
        ), $ghiDe));
    }

    private function chay($dichVu, $guiBoi = 'nguoi_gui', $khoa = self::KHOA)
    {
        $job = new SubmitBh01Job(self::MA_BAN, $guiBoi, $khoa);
        $job->submitServiceGia = $dichVu;
        $job->handle();
    }

    private function banHienTai()
    {
        return Bh01Ban::where('ma_ban', self::MA_BAN)->first();
    }

    /** @test */
    public function gui_dung_byte_tep_da_ky_va_ky_qt()
    {
        $this->ban();
        $dv = new FakeTt12SubmitService();

        $this->chay($dv);

        $this->assertSame(1, $dv->soLanGoi);
        $this->assertSame(self::NOI_DUNG, $dv->xmlNhanDuoc);
        $this->assertSame('BH_01', $dv->loaiNhanDuoc);
        $this->assertSame('202609', $dv->kyQtNhanDuoc);
        $this->assertSame('37470', $dv->maCskcbNhanDuoc, 'Token phai theo ma co so cua CHINH ban');
        $this->assertNull($dv->mauNhanDuoc, 'Khong duoc di duong gui danh muc');
    }

    /** @test */
    public function ghi_ket_qua_va_lich_su_moi_lan_gui()
    {
        $this->ban();

        $loi = new FakeTt12SubmitService();
        $loi->ketQua = array(
            'ma_ket_qua' => '500', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 500: Lỗi hệ thống', 'nguyen_van' => '{"maKetQua":"500"}',
        );
        $this->chay($loi, 'lan_1');

        $ban = $this->banHienTai();
        $this->assertSame('500', $ban->ma_ket_qua);
        $this->assertSame('Mã 500: Lỗi hệ thống', $ban->thong_diep);
        $this->assertNull($ban->dang_xu_ly_tu, 'Xong luot phai nha khoa');

        // Lan bam thu hai: xepGui giu lai khoa voi gia tri moi.
        $ban->update(array('dang_xu_ly_tu' => '2026-10-19 09:00:00'));

        $ok = new FakeTt12SubmitService();
        $ok->ketQua = array(
            'ma_ket_qua' => '200', 'ma_gd' => 'HSTH01BH_37470_1', 'thoi_gian_tiep_nhan' => '20261001083000',
            'thong_diep' => 'Tiếp nhận thành công', 'nguyen_van' => '{"maKetQua":"200"}',
        );
        $this->chay($ok, 'lan_2', '2026-10-19 09:00:00');

        $ban = $this->banHienTai();
        $this->assertSame('200', $ban->ma_ket_qua);
        $this->assertSame('HSTH01BH_37470_1', $ban->ma_gd);
        $this->assertSame('20261001083000', $ban->thoi_gian_tiep_nhan);
        $this->assertSame('Tiếp nhận thành công', $ban->thong_diep);
        $this->assertSame('lan_2', $ban->submitted_by);
        $this->assertNotNull($ban->submitted_at);

        $lichSu = Bh01LichSuGui::where('ban_id', $ban->id)->orderBy('id')->get();
        $this->assertCount(2, $lichSu);
        $this->assertSame('lan_1', $lichSu[0]->gui_boi);
        $this->assertSame('500', $lichSu[0]->ma_ket_qua);
        $this->assertNotNull($lichSu[0]->loi);
        $this->assertSame('{"maKetQua":"500"}', $lichSu[0]->nguyen_van);
        $this->assertSame('lan_2', $lichSu[1]->gui_boi);
        $this->assertSame('HSTH01BH_37470_1', $lichSu[1]->ma_gd);
        $this->assertNull($lichSu[1]->loi);
        $this->assertNotNull($lichSu[1]->gui_luc);
    }

    /** @test */
    public function tep_bi_sua_thi_khong_gui()
    {
        $ban = $this->ban();
        Storage::disk('exportTt12')->put(self::TEP, self::NOI_DUNG . ' ');
        $dv = new FakeTt12SubmitService();

        $this->chay($dv);

        $this->assertSame(0, $dv->soLanGoi);
        $lichSu = Bh01LichSuGui::where('ban_id', $ban->id)->get();
        $this->assertCount(1, $lichSu);
        $this->assertNotEmpty($lichSu[0]->loi);
        $this->assertNull($lichSu[0]->ma_ket_qua);
        $this->assertNotEmpty($this->banHienTai()->thong_diep);
        $this->assertNull($this->banHienTai()->submitted_at, 'Khong gui thi khong duoc ghi la da gui');
        $this->assertNull($this->banHienTai()->dang_xu_ly_tu);
    }

    /** @test */
    public function mat_tep_thi_khong_gui()
    {
        $ban = $this->ban();
        Storage::disk('exportTt12')->delete(self::TEP);
        $dv = new FakeTt12SubmitService();

        $this->chay($dv);

        $this->assertSame(0, $dv->soLanGoi);
        $this->assertNotEmpty(Bh01LichSuGui::where('ban_id', $ban->id)->value('loi'));
        $this->assertNull($this->banHienTai()->dang_xu_ly_tu);
    }

    /** @test */
    public function thieu_sha256_thi_khong_gui()
    {
        $this->ban(array('sha256_tep' => null));
        $dv = new FakeTt12SubmitService();

        $this->chay($dv);

        $this->assertSame(0, $dv->soLanGoi);
    }

    /** @test */
    public function chua_ky_thi_khong_gui()
    {
        foreach (array(Bh01Ban::KY_CHO, Bh01Ban::KY_LOI) as $trangThai) {
            Bh01Ban::query()->delete();
            Bh01LichSuGui::query()->delete();
            $ban = $this->ban(array('trang_thai_ky' => $trangThai));
            $dv = new FakeTt12SubmitService();

            $this->chay($dv);

            $this->assertSame(0, $dv->soLanGoi, $trangThai);
            $this->assertNotEmpty(Bh01LichSuGui::where('ban_id', $ban->id)->value('loi'));
            $this->assertNull($this->banHienTai()->dang_xu_ly_tu);
        }
    }

    /** @test */
    public function co_gui_tat_thi_khong_gui_va_nha_khoa()
    {
        config(['organization.tt12.submit_enabled' => false]);
        $ban = $this->ban();
        $dv = new FakeTt12SubmitService();

        $this->chay($dv);

        $this->assertSame(0, $dv->soLanGoi);
        $this->assertNull($this->banHienTai()->dang_xu_ly_tu);
        $this->assertSame(0, Bh01LichSuGui::where('ban_id', $ban->id)->count(),
            'Co tat thi khong ghi gi - ghi la bia');
    }

    /** @test */
    public function loi_mang_thi_nem_va_GIU_khoa_cho_lan_thu_lai()
    {
        $this->ban();
        $dv = new FakeTt12SubmitService();
        $dv->nem = new \Exception('Lỗi gọi cổng BHXH: timeout');

        try {
            $this->chay($dv);
            $this->fail('Phai nem de hang doi thu lai');
        } catch (\Exception $e) {
            $this->assertContains('timeout', $e->getMessage());
        }

        $this->assertNotNull($this->banHienTai()->dang_xu_ly_tu, 'Khong nha khoa giua cac lan thu lai');

        (new SubmitBh01Job(self::MA_BAN, 'x', self::KHOA))->failed($e);

        $this->assertNull($this->banHienTai()->dang_xu_ly_tu, 'Het luot thi nha khoa');
    }

    /** @test */
    public function handle_khong_co_tham_so_type_hint()
    {
        $this->assertSame(0, (new \ReflectionMethod(SubmitBh01Job::class, 'handle'))->getNumberOfParameters());
    }

    /** @test */
    public function stale_job_khong_gui_khi_khoa_da_doi_chu()
    {
        // Worker gui dung > KHOA_PHUT, khoa het han, nguoi dung bam lai: khoa da sang tay job
        // moi. Job cu chay sau do KHONG duoc gui - neu khong thi ca hai cung POST len cong that.
        $this->ban(array('dang_xu_ly_tu' => '2026-10-19 08:30:00'));
        $dv = new FakeTt12SubmitService();

        $this->chay($dv, 'cu', self::KHOA);

        $this->assertSame(0, $dv->soLanGoi);
        $this->assertSame('2026-10-19 08:30:00', $this->banHienTai()->dang_xu_ly_tu->format('Y-m-d H:i:s'),
            'Job cu khong duoc cham vao khoa cua job moi');
        $this->assertNull($this->banHienTai()->submitted_at);
    }

    /** @test */
    public function job_khong_co_khoa_thi_khong_gui()
    {
        $this->ban(array('dang_xu_ly_tu' => null));
        $dv = new FakeTt12SubmitService();

        $this->chay($dv, 'x', null);

        $this->assertSame(0, $dv->soLanGoi, 'Khong qua xepGui thi khong duoc gui');
    }

    /** @test */
    public function nha_khoa_khong_xoa_khoa_cua_job_khac()
    {
        $this->ban(array('dang_xu_ly_tu' => '2026-10-19 08:30:00'));

        // Job cu het luot thu lai (failed) sau khi khoa da sang tay job moi.
        (new SubmitBh01Job(self::MA_BAN, 'cu', self::KHOA))->failed(new \Exception('timeout'));

        // Co gui tat: duong nha khoa som cung phai theo khoa.
        config(['organization.tt12.submit_enabled' => false]);
        $this->chay(new FakeTt12SubmitService(), 'cu', self::KHOA);

        $this->assertSame('2026-10-19 08:30:00', $this->banHienTai()->dang_xu_ly_tu->format('Y-m-d H:i:s'));
    }
}
