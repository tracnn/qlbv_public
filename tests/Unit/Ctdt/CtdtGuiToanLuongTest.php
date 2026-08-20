<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use Tests\Support\FakeXMLSignService;
use Tests\Support\FakeCtdtSubmitService;
use Illuminate\Support\Facades\Storage;
use App\Jobs\CheckCtdtJob;
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Luoi an toan cho ca Giai doan 4: nap that -> kiem that -> ky that -> gui that -> con so
 * hien dung tren man danh sach.
 *
 * KHONG dung Queue::fake() o day: day chinh la cho phai chay ca ba job THAT.
 * Chi gia lap hai thu khong the goi that: dich vu ky va cong BHXH.
 */
class CtdtGuiToanLuongTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        $this->importer = new CtdtImporter();

        config([
            'organization.BHYT.ma_cskcb'                   => '01929',
            'organization.chung_tu_dien_tu.sign_enabled'   => true,
            'organization.chung_tu_dien_tu.submit_enabled' => true,
        ]);
    }

    private function goiHopLe()
    {
        return $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test',
                'NGAY_SINH' => '19950914', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1234567890',
            ]),
        ]]);
    }

    /**
     * CtdtImporter::nhapMotHoSo() da tu dispatch() CheckCtdtJob, va QUEUE_DRIVER=sync trong
     * moi truong test chay job do NGAY LAP TUC trong cung tien trinh - truoc khi vong lap
     * duoi day kip chay. Vong lap nay vi vay la LUOT KIEM THU HAI, khong phai lan dau. Job
     * kiem tu idempotent (xem CheckCtdtJobTest::chay_lai_khong_nhan_doi_loi) nen vo hai, chi
     * can nguoi doc biet ro de khong tuong day la noi "kich hoat" viec kiem.
     */
    private function napVaKiem($xml)
    {
        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        foreach ($kq->dsMaHoSo as $maHoSo) {
            (new CheckCtdtJob($maHoSo))->handle();
        }

        return $kq;
    }

    private function ky($maHoSo = 'YT001')
    {
        $kyGia = new FakeXMLSignService();
        (new SignCtdtJob($maHoSo))->handle($kyGia);

        return $kyGia;
    }

    private function gui($maHoSo = 'YT001', array $ketQua = null)
    {
        $guiGia = new FakeCtdtSubmitService();

        if ($ketQua !== null) {
            $guiGia->ketQua = $ketQua;
        }

        // SubmitCtdtJob::handle() KHONG nhan tham so nao: container Laravel 5.5 tiem theo
        // getClass() TRUOC khi xet gia tri mac dinh, nen mot tham so `= null` van luon bi
        // tiem. Tiem dich vu gia qua thuoc tinh cong khai thay vi tham so cua handle().
        $job = new SubmitCtdtJob($maHoSo, 'tracnn');
        $job->submitServiceGia = $guiGia;
        $job->handle();

        return $guiGia;
    }

    /** @test */
    public function nap_kiem_ky_gui_thanh_cong_thi_MaGD_hien_tren_ho_so()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertSame(0, (int) $hoSo->so_loi);
        $this->assertTrue((bool) $hoSo->is_signed);
        $this->assertSame('GD-001', $hoSo->ma_gd);
        $this->assertSame('200', $hoSo->ma_ket_qua);
        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ho_so_con_loi_KHONG_duoc_ky_va_KHONG_duoc_gui()
    {
        // Day la ly do ca Giai doan 3 va 4 ton tai: mot ho so con loi khong duoc di tiep.
        $this->napVaKiem($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]));

        $kyGia = $this->ky();
        $guiGia = $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertGreaterThan(0, (int) $hoSo->so_loi);
        $this->assertSame(0, $kyGia->soLanGoi, 'Ho so con loi khong duoc ky');
        $this->assertSame(0, $guiGia->soLanGoi, 'Ho so con loi khong duoc gui');
        $this->assertNull($hoSo->ma_gd);
        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ho_so_CHUA_KIEM_khong_duoc_ky_va_khong_duoc_gui()
    {
        // May chu chua cai worker JobCtdt roi vao dung tinh huong nay. Neu cua chan khong
        // dong o day thi moi ho so tren may do se duoc gui len cong ma khong ai kiem.
        //
        // Moi truong test dat QUEUE_DRIVER=sync nen CheckCtdtJob ma nhapTuChuoi() tu dong
        // dispatch() chay NGAY LAP TUC trong cung tien trinh - khac voi may chu that, noi
        // hang doi dung driver database va cho worker JobCtdt lay ra chay. De mo phong dung
        // tinh trang "da nap nhung chua kiem" (worker chua kip chay), reset lai checked_at
        // ve null sau khi nap - day khong phai gia lap dich vu ky/gui, chi la dua du lieu
        // ve dung trang thai ma mot hang doi that chua xu ly toi se co.
        $this->importer->nhapTuChuoi($this->goiHopLe(), ['macskcb' => '01929']);
        CtdtHoSo::where('ma_ho_so', 'YT001')->update(['checked_at' => null]);

        $kyGia = $this->ky();
        $guiGia = $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertNull($hoSo->checked_at);
        $this->assertSame(0, $kyGia->soLanGoi);
        $this->assertSame(0, $guiGia->soLanGoi);
        $this->assertSame(CtdtTrangThaiGui::CHUA_KIEM, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ho_so_CHUA_KIEM_khong_duoc_gui_DU_da_co_tep_da_ky()
    {
        // Ky TRUOC roi moi dua checked_at ve null: neu de ho so chua kiem tu dau thi
        // SignCtdtJob tu choi ky, duong_dan_da_ky rong, va job gui thoat o nhanh "khong tim
        // thay tep da ky" - tuc khang dinh soLanGoi === 0 duoc thoa nho mot tuyen phong thu
        // PHU, khong phai nho cua chan dang duoc canh (CtdtQuyetDinhGui). Co tep tren dia roi
        // thi chi con dung cua chan giu lai.
        $this->napVaKiem($this->goiHopLe());
        $this->ky();

        $this->assertNotEmpty(CtdtHoSo::first()->duong_dan_da_ky, 'Phai co tep da ky that');

        CtdtHoSo::first()->update(['checked_at' => null]);

        $guiGia = $this->gui();

        $this->assertSame(0, $guiGia->soLanGoi, 'Ho so chua kiem khong duoc gui');
        $this->assertNull(CtdtHoSo::first()->ma_gd);
    }

    /** @test */
    public function ho_so_con_loi_khong_duoc_gui_DU_da_co_tep_da_ky()
    {
        // Cung ly do voi test tren: ky TRUOC de co tep that tren dia, roi moi ap so_loi > 0,
        // de khang dinh soLanGoi === 0 chi con quy ve dung cua chan so_loi cua
        // CtdtQuyetDinhGui, khong lan sang nhanh "khong tim thay tep da ky".
        $this->napVaKiem($this->goiHopLe());
        $this->ky();

        $this->assertNotEmpty(CtdtHoSo::first()->duong_dan_da_ky, 'Phai co tep da ky that');

        CtdtHoSo::first()->update(['so_loi' => 3]);

        $guiGia = $this->gui();

        $this->assertSame(0, $guiGia->soLanGoi, 'Ho so con loi khong duoc gui');
        $this->assertNull(CtdtHoSo::first()->ma_gd);
    }

    /** @test */
    public function goi_gui_len_cong_la_tep_DA_KY_chu_khong_phai_phong_bi_tran()
    {
        $this->napVaKiem($this->goiHopLe());

        $kyGia = $this->ky();
        $kyGia->ketQua = ['isSigned' => true, 'data' => '<HSCHUNGTU>CO-CHU-KY</HSCHUNGTU>', 'method' => 'HSM'];
        (new SignCtdtJob('YT001'))->handle($kyGia);

        $guiGia = $this->gui();

        $this->assertContains('CO-CHU-KY', $guiGia->xmlNhanDuoc);
    }

    /** @test */
    public function nap_lai_sau_khi_da_gui_thi_reset_trang_thai_va_giu_lich_su()
    {
        // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui cu
        // noi ve mot ban khac. Nhung MaGD cu la dau vet doi soat - phai giu lai.
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui();

        $this->assertSame('GD-001', CtdtHoSo::first()->ma_gd);

        $this->napVaKiem($this->goiHopLe());

        $hoSo = CtdtHoSo::first();

        $this->assertNull($hoSo->ma_gd, 'Nap lai phai reset ket qua gui cu');
        $this->assertFalse((bool) $hoSo->is_signed, 'Nap lai phai reset trang thai ky');
        // Dong nay do CtdtLuuHoSo::noiLichSu() ghi luc NAP LAI, KHONG PHAI
        // SubmitCtdtJob::noiLichSu() (ham rieng, chi chay luc GUI). Test nay khong phu duong
        // ghi lich su cua SubmitCtdtJob - xem SubmitCtdtJobTest::lich_su_gui_giu_ca_ba_lan
        // cho duong do.
        $this->assertContains('GD-001', (string) $hoSo->lich_su_gui, 'Phai giu dau vet lan gui truoc');
    }

    /** @test */
    public function cong_tu_choi_thi_trang_thai_thanh_CONG_TU_CHOI()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui('YT001', [
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: fileBase64Str không hợp lệ', 'nguyen_van' => '{"MaKetQua":"205"}',
        ]);

        $this->assertSame(CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::cua(CtdtHoSo::first()));
    }

    /** @test */
    public function ba_dich_vu_deu_ky_va_gui_duoc()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->napVaKiem($this->goiGbt([
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Tran Thi Test',
            'NGAY_SINH' => '19480826', 'NGAY_TV' => '202510070200', 'MA_THE' => 'DN1',
        ]));
        $this->napVaKiem($this->goiGcs([
            'MA_GCS' => 'GCS-1', 'HOTEN_NND' => 'Le Thi Test',
            'NGAYSINH_NND' => '19950101', 'NGAY_SINH_CON' => '202601011200', 'MA_THE_NND' => 'DN2',
        ]));

        $this->assertSame(3, CtdtHoSo::count());

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->ky($hoSo->ma_ho_so);
            $this->gui($hoSo->ma_ho_so);
        }

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->assertTrue((bool) $hoSo->is_signed, $hoSo->ma_ho_so . ' chua ky duoc');
            $this->assertSame('GD-001', $hoSo->ma_gd, $hoSo->ma_ho_so . ' chua gui duoc');
        }
    }

    /** @test */
    public function ky_that_bai_thi_khong_gui_va_ly_do_that_hien_len_man_chi_tiet()
    {
        // Ca thuong gap nhat khi trien khai: bat sign_enabled nhung quen usb_token_sign.
        // Ho so dung lai o "chua ky", va ly do THAT phai den duoc nguoi van hanh - khong
        // thi ho di tim nut ky (da bam roi) thay vi di cam lai USB token.
        $this->napVaKiem($this->goiHopLe());

        $kyGia = new FakeXMLSignService();
        $kyGia->ketQua = [
            'isSigned' => false, 'data' => '<X/>', 'method' => 'USB Token',
            'error' => 'Khong ket noi duoc dich vu ky cuc bo',
        ];
        (new SignCtdtJob('YT001'))->handle($kyGia);

        $guiGia = $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertSame(0, $guiGia->soLanGoi, 'Ho so chua ky khong duoc gui');
        $this->assertContains('Khong ket noi duoc', (string) $hoSo->signed_error);
    }
}
