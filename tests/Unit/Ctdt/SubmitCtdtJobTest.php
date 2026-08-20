<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtSubmitService;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Dich vu gui gia, ke thua lop that de giu dung chu ky phuong thuc.
 */
class FakeCtdtSubmitService extends CtdtSubmitService
{
    public $ketQua = [
        'ma_ket_qua' => '200', 'ma_gd' => 'GD-001',
        'thoi_gian_tiep_nhan' => '20260820083000',
        'thong_diep' => 'Mã 200: Thành công', 'nguyen_van' => '{"MaKetQua":"200"}',
    ];
    public $nem = null;
    public $soLanGoi = 0;
    public $xmlNhanDuoc = null;
    public $dichVuNhanDuoc = null;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle that.
    }

    public $maCskcbNhanDuoc = null;

    public function gui($xmlDaKy, $dichVu, $maCskcb)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlDaKy;
        $this->dichVuNhanDuoc = $dichVu;
        $this->maCskcbNhanDuoc = $maCskcb;

        if ($this->nem !== null) {
            throw $this->nem;
        }

        return $this->ketQua;
    }
}

class SubmitCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function hoSo(array $ghiDe = [], $noiDungDaKy = '<HSCHUNGTU>DA-KY</HSCHUNGTU>')
    {
        $duongDan = 'da-ky/CT2025/YT001.xml';

        if ($noiDungDaKy !== null) {
            Storage::disk('exportCtdt')->put($duongDan, $noiDungDaKy);
        }

        return CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
            'is_signed' => true, 'sign_method' => 'HSM',
            'duong_dan_da_ky' => $duongDan,
        ], $ghiDe))->fresh();
    }

    private function chay($gui, $maHoSo = 'YT001', $nguoiGui = 'tracnn')
    {
        (new SubmitCtdtJob($maHoSo, $nguoiGui))->handle($gui);
    }

    /** @test */
    public function gui_thanh_cong_ghi_du_ma_gd_ma_ket_qua_va_thoi_gian()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('GD-001', $hoSo->ma_gd);
        $this->assertSame('200', $hoSo->ma_ket_qua);
        $this->assertSame('20260820083000', $hoSo->thoi_gian_tiep_nhan);
        $this->assertNotNull($hoSo->submitted_at);
        $this->assertSame('tracnn', $hoSo->submitted_by);
        $this->assertNull($hoSo->submit_error);
    }

    /** @test */
    public function gui_dung_noi_dung_tep_DA_KY_chu_khong_dung_lai_phong_bi()
    {
        // Dung lai phong bi o day la gui mot goi KHONG co chu ky, va cong tra 205 - hoac
        // te hon, nhan mot ho so khong co gia tri phap ly.
        $this->hoSo([], '<HSCHUNGTU><CHUKYDONVI>chu-ky-that</CHUKYDONVI></HSCHUNGTU>');
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertContains('chu-ky-that', $gui->xmlNhanDuoc);
        $this->assertSame('CT2025', $gui->dichVuNhanDuoc);
    }

    /** @test */
    public function truyen_ma_co_so_cua_CHINH_ho_so_xuong_dich_vu_gui()
    {
        // Neu token va tai khoan trong body thuoc hai co so khac nhau thi cong van nhan, va
        // ho so bi ghi sai don vi gui - hong IM LANG cho toi luc doi soat. Day la bay ma
        // SubmitXml3176Job da dinh mot lan roi.
        $this->hoSo(['macskcb' => '37470']);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame('37470', $gui->maCskcbNhanDuoc);
    }

    /** @test */
    public function cong_tu_choi_thi_ghi_submit_error_va_giu_nguyen_van()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();
        $gui->ketQua = [
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: fileBase64Str không hợp lệ',
            'nguyen_van' => '{"MaKetQua":"205","ChiTiet":"sai the goc"}',
        ];

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('205', $hoSo->ma_ket_qua);
        $this->assertNotEmpty($hoSo->submit_error);
        $this->assertContains('sai the goc', (string) $hoSo->submitted_message);
    }

    /** @test */
    public function co_submit_enabled_tat_thi_KHONG_ghi_gi_ca()
    {
        // Khi chuc nang gui dang tat thi khong co lan gui nao dien ra, nen ghi submit_error
        // la BIA - nguoi doc se tuong da thu gui va that bai.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNull($hoSo->submit_error);
        $this->assertNull($hoSo->submitted_at);
        $this->assertNull($hoSo->ma_ket_qua);
    }

    /** @test */
    public function ho_so_chua_kiem_thi_ghi_loi_va_khong_goi_mang()
    {
        $this->hoSo(['checked_at' => null]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertContains('chưa kiểm', mb_strtolower((string) CtdtHoSo::first()->submit_error));
    }

    /** @test */
    public function ho_so_con_loi_thi_ghi_loi_va_khong_goi_mang()
    {
        $this->hoSo(['so_loi' => 2]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function ho_so_chua_ky_thi_ghi_loi_va_khong_goi_mang()
    {
        // Gui len cong thi cong cung tu choi. Chan tai cho vua khong ton mot vong goi mang,
        // vua cho thong bao ro hon thong bao cua cong.
        $this->hoSo(['is_signed' => false]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function tep_da_ky_bien_mat_thi_ghi_loi_chu_khong_nem()
    {
        // Tep tren dia co the bi don dep. Nem chi lam hang doi thu lai ba lan cho cung mot
        // ket qua, va nguoi van hanh khong biet vi sao.
        $this->hoSo([], null);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function loi_mang_thi_NEM_de_hang_doi_thu_lai()
    {
        // Mang chap la loi TAM THOI - phai de hang doi thu lai. Nuot no thanh mot dong
        // submit_error la ho so mat co hoi duoc gui lai tu dong.
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();
        $gui->nem = new \Exception('Loi goi cong BHXH: Connection refused');

        $this->expectException(\Exception::class);

        $this->chay($gui);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui, 'KHONG_TON_TAI');

        $this->assertSame(0, $gui->soLanGoi);
    }

    /** @test */
    public function gui_lai_lan_hai_thi_noi_them_lich_su_chu_khong_xoa()
    {
        // MaGD cu la dau vet doi soat voi BHXH. Ghi de ma khong giu lai la mat dau vet cua
        // mot lan gui da that su xay ra.
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $gui->ketQua['ma_gd'] = 'GD-002';
        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('GD-002', $hoSo->ma_gd);
        $this->assertContains('GD-001', (string) $hoSo->lich_su_gui, 'Phai giu dau vet lan gui truoc');
    }

    /** @test */
    public function lich_su_gui_giu_ca_ba_lan()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        foreach (['GD-A', 'GD-B', 'GD-C'] as $ma) {
            $gui->ketQua['ma_gd'] = $ma;
            $this->chay($gui);
        }

        $lichSu = (string) CtdtHoSo::first()->lich_su_gui;

        $this->assertContains('GD-A', $lichSu);
        $this->assertContains('GD-B', $lichSu);
        $this->assertSame('GD-C', CtdtHoSo::first()->ma_gd);
    }
}
