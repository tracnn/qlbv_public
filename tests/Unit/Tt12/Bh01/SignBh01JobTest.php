<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Jobs\SignBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01Dong;
use App\Services\Tt12\Bh01\Bh01PhongBi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DungBangBh01Sqlite;
use Tests\Support\FakeXMLSignService;
use Tests\TestCase;

class SignBh01JobTest extends TestCase
{
    use DungBangBh01Sqlite;

    const MA_BAN = 'BH01_01929_202609_01';

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();

        Storage::fake('exportTt12');

        config(['organization.tt12.sign_enabled' => true]);
    }

    /** Cac gia tri dong da chuan hoa (dung cai se in ra XML) */
    private function giaTriDong($stt, $maLk)
    {
        return array(
            'stt' => $stt, 'ma_lk' => $maLk, 'ho_ten' => 'NGUYEN VAN ' . $maLk . ' & CO',
            'ngay_sinh' => '198001010000', 'gioi_tinh' => '1', 'ma_the_bhyt' => 'DN4010000000001',
            'ma_benh_chinh' => 'E11', 'ngay_vao' => '202609010800', 'ngay_vao_noi_tru' => '',
            'ngay_ra' => '202609010900', 'so_ngay_dtri' => '1', 'ma_loai_kcb' => '01',
            't_tongchi_bv' => '100', 't_tongchi_bh' => '80', 't_bhtt' => '70', 't_bncct' => '10',
            't_bntt' => '20', 't_nguonkhac' => '0', 'ma_cskcb' => '01929', 'nam_qt' => '2026',
            'thang_qt' => '09',
        );
    }

    private function ban()
    {
        $ban = Bh01Ban::create(array(
            'ma_ban' => self::MA_BAN, 'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09',
            'so_ban' => 1, 'so_dong' => 2, 'id_ds_chitiet' => 'Id-abc',
            'trang_thai_ky' => Bh01Ban::KY_CHO,
        ));

        // Chen NGUOC thu tu stt: job phai tu sap theo stt chu khong dua vao thu tu chen.
        foreach (array(array(2, 'B'), array(1, 'A')) as $d) {
            Bh01Dong::create(array_merge($this->giaTriDong($d[0], $d[1]), array(
                'ban_id' => $ban->id, 'canh_bao' => 'canh bao khong duoc lot vao XML',
            )));
        }

        return $ban;
    }

    private function chay($dichVu)
    {
        $job = new SignBh01Job(self::MA_BAN);
        $job->signServiceGia = $dichVu;
        $job->handle();
    }

    /** @test */
    public function ky_xong_ghi_tep_va_sha256()
    {
        $this->ban();
        $ky = new FakeXMLSignService();
        $ky->ketQua = array('isSigned' => true, 'data' => '<HSTH01BH>DA KY</HSTH01BH>', 'method' => 'HSM');

        $this->chay($ky);

        $ban = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(Bh01Ban::KY_XONG, $ban->trang_thai_ky);
        $this->assertSame('HSM', $ban->sign_method);
        $this->assertNotNull($ban->signed_at);
        $this->assertNull($ban->signed_error);
        $this->assertSame('BH01/202609/' . self::MA_BAN . '.xml', $ban->duong_dan_da_ky);

        $tep = Storage::disk('exportTt12')->get($ban->duong_dan_da_ky);
        $this->assertSame('<HSTH01BH>DA KY</HSTH01BH>', $tep);
        $this->assertSame(hash('sha256', $tep), $ban->sha256_tep);
    }

    /** @test */
    public function xml_gui_vao_dich_vu_ky_dung_bang_phong_bi()
    {
        $this->ban();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $mongDoi = Bh01PhongBi::dung('Id-abc', array($this->giaTriDong(1, 'A'), $this->giaTriDong(2, 'B')));

        $this->assertSame($mongDoi, $ky->xmlNhanDuoc);
        $this->assertNotContains('canh bao', $ky->xmlNhanDuoc);
    }

    /** @test */
    public function ky_hong_ghi_ky_loi_khong_ghi_tep()
    {
        $this->ban();
        $ky = new FakeXMLSignService();
        $ky->ketQua = array('isSigned' => false, 'error' => 'USB Token bi rut');

        $this->chay($ky);

        $ban = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(Bh01Ban::KY_LOI, $ban->trang_thai_ky);
        $this->assertSame('USB Token bi rut', $ban->signed_error);
        $this->assertNull($ban->duong_dan_da_ky);
        $this->assertNull($ban->sha256_tep);
        $this->assertCount(0, Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function ky_bao_thanh_cong_ma_du_lieu_rong_thi_la_ky_loi()
    {
        // Ghi tep rong roi danh dau da ky la mo duong gui mot tep rong len cong that.
        $this->ban();
        $ky = new FakeXMLSignService();
        $ky->ketQua = array('isSigned' => true, 'data' => '');

        $this->chay($ky);

        $ban = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(Bh01Ban::KY_LOI, $ban->trang_thai_ky);
        $this->assertCount(0, Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function co_ky_tat_thi_khong_lam_gi()
    {
        config(['organization.tt12.sign_enabled' => false]);
        $this->ban();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $ban = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(0, $ky->soLanGoi);
        $this->assertSame(Bh01Ban::KY_CHO, $ban->trang_thai_ky);
        $this->assertNull($ban->signed_error);
        $this->assertCount(0, Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function ky_lai_cung_noi_dung_truoc_chu_ky()
    {
        $this->ban();

        $lan1 = new FakeXMLSignService();
        $lan1->ketQua = array('isSigned' => false, 'error' => 'HSM khong phan hoi');
        $this->chay($lan1);

        // "Ky lai" di qua Bh01XepHang::xepKy, noi dat lai cho_ky truoc khi xep job.
        Bh01Ban::where('ma_ban', self::MA_BAN)->update(array('trang_thai_ky' => Bh01Ban::KY_CHO));

        $lan2 = new FakeXMLSignService();
        $this->chay($lan2);

        $this->assertNotNull($lan1->xmlNhanDuoc);
        $this->assertSame($lan1->xmlNhanDuoc, $lan2->xmlNhanDuoc);
        $this->assertSame(Bh01Ban::KY_XONG, Bh01Ban::where('ma_ban', self::MA_BAN)->value('trang_thai_ky'));
    }

    /** @test */
    public function ban_da_gui_cong_thi_khong_ky_de_tep()
    {
        // Tep da ky cua ban da gui la dau vet doi soat: ky de se lam tep tai ve khac
        // tep cong da nhan.
        $ban = $this->ban();
        $ban->update(array('submitted_at' => '2026-10-01 10:00:00', 'ma_ket_qua' => '200'));
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
        $this->assertCount(0, Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function het_luot_thu_lai_thi_ghi_ky_loi()
    {
        // Khong co failed(): ban nam o "cho ky" mai mai va nut "Ky lai" khong bao gio hien.
        $this->ban();

        (new SignBh01Job(self::MA_BAN))->failed(new \Exception('Ket noi HSM that bai'));

        $ban = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(Bh01Ban::KY_LOI, $ban->trang_thai_ky);
        $this->assertContains('Ket noi HSM that bai', $ban->signed_error);
    }

    /** @test */
    public function handle_khong_co_tham_so_type_hint()
    {
        $this->assertSame(0, (new \ReflectionMethod(SignBh01Job::class, 'handle'))->getNumberOfParameters());
    }

    /** @test */
    public function job_khong_ky_lai_ban_da_ky()
    {
        // Job ky con nam trong hang doi (bam hai lan, retry_after) chay sau khi ban da ky: ky
        // de se doi tep va sha256 ngay duoi chan mot job gui dang doc tep.
        $this->ban();
        $this->chay(new FakeXMLSignService());
        $truoc = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $tepTruoc = Storage::disk('exportTt12')->get($truoc->duong_dan_da_ky);

        $lan2 = new FakeXMLSignService();
        $lan2->ketQua = array('isSigned' => true, 'data' => '<KHAC/>', 'method' => 'HSM');
        $this->chay($lan2);

        $sau = Bh01Ban::where('ma_ban', self::MA_BAN)->first();
        $this->assertSame(0, $lan2->soLanGoi);
        $this->assertSame($truoc->sha256_tep, $sau->sha256_tep);
        $this->assertSame($tepTruoc, Storage::disk('exportTt12')->get($sau->duong_dan_da_ky));
        $this->assertSame(Bh01Ban::KY_XONG, $sau->trang_thai_ky);
    }

    /** @test */
    public function ban_ky_loi_chua_xep_lai_thi_khong_ky()
    {
        $this->ban();
        Bh01Ban::where('ma_ban', self::MA_BAN)->update(array('trang_thai_ky' => Bh01Ban::KY_LOI));
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function timeout_nho_hon_retry_after()
    {
        // timeout >= retry_after thi hang doi 'database' coi job dang ky la chet va giao them
        // mot ban cho worker khac - hai lan ky chay chong.
        $this->assertLessThan(
            (int) config('queue.connections.database.retry_after'),
            (new SignBh01Job('x'))->timeout
        );
    }
}
