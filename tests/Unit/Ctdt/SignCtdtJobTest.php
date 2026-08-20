<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SignCtdtJob;
use App\Services\XMLSignService;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Lop ky gia, ke thua lop that de giu dung chu ky phuong thuc.
 * KHONG dung createMock(): PHPUnit 6 sinh deprecation ReflectionType voi lop co kieu tra ve.
 */
class FakeXMLSignService extends XMLSignService
{
    public $ketQua = ['isSigned' => true, 'data' => '<DAKY/>', 'method' => 'USB Token'];
    public $xmlNhanDuoc = null;
    public $soLanGoi = 0;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle/Config that.
    }

    public function signXml($xmlContent)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlContent;

        return $this->ketQua;
    }
}

class SignCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        config(['organization.chung_tu_dien_tu.sign_enabled' => true]);
    }

    private function hoSo(array $ghiDe = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => '20260820',
            'so_chung_tu' => 1, 'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
        ], $ghiDe));

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'noi_dung_goc' => '<CT03><MA_YTE>YT001</MA_YTE></CT03>',
        ]);

        return $hoSo->fresh();
    }

    private function chay($ky, $maHoSo = 'YT001')
    {
        (new SignCtdtJob($maHoSo))->handle($ky);
    }

    /** @test */
    public function ky_thanh_cong_thi_ghi_du_bon_cot()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $hoSo = CtdtHoSo::first();

        $this->assertTrue((bool) $hoSo->is_signed);
        $this->assertSame('USB Token', $hoSo->sign_method);
        $this->assertNotNull($hoSo->signed_at);
        $this->assertNull($hoSo->signed_error);
        $this->assertNotEmpty($hoSo->duong_dan_da_ky);
    }

    /** @test */
    public function luu_tep_da_ky_len_disk_exportCtdt()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => true, 'data' => '<HSCHUNGTU>DA-KY</HSCHUNGTU>', 'method' => 'HSM'];

        $this->chay($ky);

        $duongDan = CtdtHoSo::first()->duong_dan_da_ky;

        Storage::disk('exportCtdt')->assertExists($duongDan);
        $this->assertSame('<HSCHUNGTU>DA-KY</HSCHUNGTU>', Storage::disk('exportCtdt')->get($duongDan));
    }

    /** @test */
    public function ky_dung_phong_bi_dung_tu_du_lieu_da_luu()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $goi = simplexml_load_string($ky->xmlNhanDuoc);

        $this->assertNotFalse($goi, 'Phai dua XML hop le cho dich vu ky');
        $this->assertSame('HSCHUNGTU', $goi->getName());
        $this->assertTrue(isset($goi->CHUKYDONVI), 'Phai co the CHUKYDONVI de dich vu ky ghi vao');
        $this->assertSame('YT001', (string) simplexml_load_string(base64_decode(
            (string) $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO->NOIDUNGFILE
        ))->MA_YTE);
    }

    /** @test */
    public function ky_that_bai_thi_ghi_signed_error_va_KHONG_dat_is_signed()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<X/>', 'method' => 'HSM', 'error' => 'HSM khong phan hoi'];

        $this->chay($ky);

        $hoSo = CtdtHoSo::first();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertContains('HSM khong phan hoi', (string) $hoSo->signed_error);
        $this->assertEmpty($hoSo->duong_dan_da_ky, 'Ky that bai thi khong duoc de lai duong dan');
    }

    /** @test */
    public function ky_that_bai_thi_KHONG_ghi_tep_nao()
    {
        // Ghi mot tep chua ky vao duong da ky la de lai mot qua bom: lan gui sau doc dung
        // tep do va gui len cong mot goi khong co chu ky.
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<CHUA-KY/>', 'method' => null, 'error' => 'loi'];

        $this->chay($ky);

        $this->assertEmpty(Storage::disk('exportCtdt')->allFiles());
    }

    /** @test */
    public function co_sign_enabled_tat_thi_khong_lam_gi()
    {
        // Job co the nam cho trong hang doi rat lau; giua luc do cau hinh co the da bi tat.
        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
        $this->assertFalse((bool) CtdtHoSo::first()->is_signed);
        $this->assertNull(CtdtHoSo::first()->signed_error, 'Co tat thi khong ghi loi - ghi la bia');
    }

    /** @test */
    public function ho_so_con_loi_chan_thi_khong_ky()
    {
        // Ky mot ho so con loi la ton mot thao tac dat nhat trong chuoi cho mot ho so chac
        // chan khong duoc gui.
        $this->hoSo(['so_loi' => 3]);
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
        $this->assertFalse((bool) CtdtHoSo::first()->is_signed);
    }

    /** @test */
    public function ho_so_chua_kiem_thi_khong_ky()
    {
        // so_loi = 0 cua mot ho so chua kiem khong co nghia la sach.
        $this->hoSo(['checked_at' => null]);
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        // Ho so co the bi xoa trong luc job cho trong hang doi. Nem chi lam job that bai va
        // thu lai hai lan cho cung mot ket qua.
        $ky = new FakeXMLSignService();

        $this->chay($ky, 'KHONG_TON_TAI');

        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function ho_so_khong_co_chung_tu_thi_ghi_loi_chu_khong_nem()
    {
        CtdtHoSo::create([
            'ma_ho_so' => 'YT002', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 0,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
        ]);

        $ky = new FakeXMLSignService();

        $this->chay($ky, 'YT002');

        $hoSo = CtdtHoSo::where('ma_ho_so', 'YT002')->first();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertNotEmpty($hoSo->signed_error);
        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function chay_lai_thi_ghi_de_chu_khong_nhan_doi_tep()
    {
        // Hang doi co the giao lai job sau khi that bai giua chung.
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);
        $this->chay($ky);

        $this->assertCount(1, Storage::disk('exportCtdt')->allFiles());
    }

    /** @test */
    public function duong_dan_khong_chua_ky_tu_nguy_hiem_cua_ma_ho_so()
    {
        // ma_ho_so co the chua '#' (nhanh lui GUID) va den tu XML ben ngoai. Ghep thang vao
        // duong dan tep la mo duong cho '../' di ra khoi thu muc.
        $hoSo = $this->hoSo(['ma_ho_so' => '../../hiem/YT#003']);

        $ky = new FakeXMLSignService();
        $this->chay($ky, '../../hiem/YT#003');

        $duongDan = CtdtHoSo::where('ma_ho_so', '../../hiem/YT#003')->first()->duong_dan_da_ky;

        $this->assertNotContains('..', (string) $duongDan);
        $this->assertNotContains('#', (string) $duongDan);
    }
}
