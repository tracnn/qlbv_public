<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12DongThuocPx;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Models\BHYT\Tt12\Tt12LichSuGui;
use App\Services\BHYT\DanhSachCoSo;
use App\Http\Controllers\BHYT\BHYTTt12Controller;

/**
 * Kiem THANG tren controller, khong qua HTTP: bo test nay khong co phien dang nhap dung
 * san (xem ghi chu trong Tt12ManImportTest), va cai can kiem la NHANH XU LY chu khong phai
 * tang middleware. Cac chot quyen duoc kiem rieng o Tt12ManImportTest.
 */
class Tt12ControllerTest extends TestCase
{
    use DungBangTt12Sqlite;

    /** @var string thu muc tam chua tep .xlsx dung trong test */
    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Storage::fake('exportTt12');

        // DanhSachCoSo doc HIS qua Cache::remember. Moi cache truoc de test khong phu thuoc
        // vao ket noi HIS - driver cache trong bo test la 'array' nen khong ro ri sang test
        // khac.
        Cache::put(DanhSachCoSo::KHOA_CACHE, array('01929' => '01929 - Bach Mai'), 60);

        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tt12-ctrl-' . uniqid();
        mkdir($this->thuMuc);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $tep) {
            unlink($tep);
        }

        rmdir($this->thuMuc);

        parent::tearDown();
    }

    private function controller()
    {
        return new BHYTTt12Controller();
    }

    private function ghiXlsx($ten, array $hang)
    {
        $duongDan = $this->thuMuc . DIRECTORY_SEPARATOR . $ten;

        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('DATA');
        $ws->fromArray($hang, null, 'A1');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
        $writer->save($duongDan);
        $sheet->disconnectWorksheets();

        return $duongDan;
    }

    private function headerMau01()
    {
        return array('STT', 'MA_KHOA', 'TEN_KHOA', 'BAN_KHAM', 'GIUONG_PD', 'GIUONG_TK',
                     'GIUONG_HSTC', 'GIUONG_HSCC', 'TU_NGAY', 'DEN_NGAY', 'MA_CSKCB');
    }

    /** Tep tai len o che do test - khong qua move_uploaded_file() */
    private function tepTaiLen($duongDan, $tenHienThi = null)
    {
        return new UploadedFile(
            $duongDan,
            $tenHienThi ?: basename($duongDan),
            null,
            filesize($duongDan),
            null,
            true
        );
    }

    private function yeuCauTaiLen(array $tham, array $tep = array())
    {
        return Request::create('/bhyt/tt12/import/upload', 'POST', $tham, array(), $tep);
    }

    /** @return array noi dung JSON tra ve */
    private function than($phanHoi)
    {
        return json_decode($phanHoi->getContent(), true);
    }

    // -- uploadData ------------------------------------------------------------------

    /** @test */
    public function tai_len_khong_co_tep_nao_thi_tra_400()
    {
        $phanHoi = $this->controller()->uploadData(
            $this->yeuCauTaiLen(array('ma_cskcb' => '01929'))
        );

        $this->assertSame(400, $phanHoi->getStatusCode());
        $this->assertFalse($this->than($phanHoi)['thanh_cong']);
    }

    /** @test */
    public function thieu_ma_co_so_thi_tra_422()
    {
        $tep = $this->ghiXlsx('m1.xlsx', array($this->headerMau01()));

        $phanHoi = $this->controller()->uploadData($this->yeuCauTaiLen(
            array(),
            array('tepExcel' => $this->tepTaiLen($tep))
        ));

        $this->assertSame(422, $phanHoi->getStatusCode());
        $this->assertContains('Chưa chọn cơ sở', $this->than($phanHoi)['thong_diep']);
        $this->assertSame(0, Tt12HoSo::count());
    }

    /** @test */
    public function ma_co_so_khong_co_trong_danh_sach_thi_tra_422()
    {
        // Endpoint co the bi goi thang khong qua form. Mot ma co so la se tao ho so khong
        // bao gio gui duoc.
        $tep = $this->ghiXlsx('m1.xlsx', array($this->headerMau01()));

        $phanHoi = $this->controller()->uploadData($this->yeuCauTaiLen(
            array('ma_cskcb' => '99999'),
            array('tepExcel' => $this->tepTaiLen($tep))
        ));

        $this->assertSame(422, $phanHoi->getStatusCode());
        $this->assertContains('99999', $this->than($phanHoi)['thong_diep']);
        $this->assertSame(0, Tt12HoSo::count());
    }

    /** @test */
    public function tep_sai_phan_mo_rong_bi_tu_choi_ma_khong_tao_ho_so()
    {
        $duongDan = $this->thuMuc . DIRECTORY_SEPARATOR . 'ghi-chu.txt';
        file_put_contents($duongDan, 'khong phai Excel');

        $phanHoi = $this->controller()->uploadData($this->yeuCauTaiLen(
            array('ma_cskcb' => '01929'),
            array('tepExcel' => $this->tepTaiLen($duongDan))
        ));

        $than = $this->than($phanHoi);

        $this->assertFalse($than['thanh_cong']);
        $this->assertContains('.xlsx', $than['chi_tiet'][0]['loi']);
        $this->assertSame(0, Tt12HoSo::count());
    }

    /** @test */
    public function tep_xlsx_hop_le_nap_thanh_cong_va_bao_dung_so_o_tu_dien()
    {
        $tep = $this->ghiXlsx('m1.xlsx', array(
            $this->headerMau01(),
            array(1, 'K01', 'Kham benh', 3, 0, 0, 0, 0, '20260101', '', ''),
            array(2, 'K02', 'Noi tong hop', 0, 40, 42, 5, 3, '20260101', '', '01929'),
        ));

        $phanHoi = $this->controller()->uploadData($this->yeuCauTaiLen(
            array('ma_cskcb' => '01929'),
            array('tepExcel' => $this->tepTaiLen($tep, 'danh-muc-khoa.xlsx'))
        ));

        $than = $this->than($phanHoi);

        $this->assertTrue($than['thanh_cong'], json_encode($than));
        $this->assertCount(1, $than['chi_tiet']);
        $this->assertSame('danh-muc-khoa.xlsx', $than['chi_tiet'][0]['ten_tep']);
        $this->assertSame('MAU_01', $than['chi_tiet'][0]['mau']);
        $this->assertSame(2, $than['chi_tiet'][0]['so_dong']);
        $this->assertSame(1, $than['chi_tiet'][0]['so_o_dien_them']);

        $this->assertSame(1, Tt12HoSo::count());
        $this->assertSame(2, Tt12Dong::count());
    }

    // -- delete ----------------------------------------------------------------------

    private function hoSoDayDu(array $ghiDe = array())
    {
        $duongDan = 'MAU_05/202608/TT12_MAU_05_01929_20260825_001.xml';

        Storage::disk('exportTt12')->put($duongDan, '<HSDANHMUC/>');

        $hoSo = Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => 'TT12_MAU_05_01929_20260825_001',
            'mau' => 'MAU_05', 'loai_hs' => '12', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'is_signed' => true, 'duong_dan_da_ky' => $duongDan,
            'ma_ket_qua' => '205',
        ), $ghiDe));

        $dong = Tt12Dong::create(array(
            'ho_so_id' => $hoSo->id, 'stt' => 1,
            'du_lieu' => array('MA_DICH_VU' => 'DV01'),
        ));

        Tt12DongThuocPx::create(array(
            'dong_id' => $dong->id, 'stt' => 1, 'ma_thuoc' => 'TPX01',
        ));

        Tt12Loi::create(array(
            'ho_so_id' => $hoSo->id, 'ma_loi' => 'X', 'muc_do' => 'loi', 'mo_ta' => 'y',
        ));

        Tt12LichSuGui::create(array(
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => $hoSo->ma_ho_so,
            'gui_luc' => '2026-08-25 10:00:00', 'ma_ket_qua' => '205',
        ));

        return $hoSo;
    }

    /** @test */
    public function xoa_ho_so_khong_de_lai_dong_thuoc_px_mo_coi()
    {
        // Dung lo hong da duoc sua o Tt12Importer::doSach() nhung tai xuat o duong xoa thu
        // hai: tt12_dong bien mat con cac ban ghi con nam lai tro toi mot dong_id khong con
        // ton tai - khong khoa ngoai, khong CASCADE, khong truy van nao tim ra duoc nua.
        $hoSo = $this->hoSoDayDu();

        $phanHoi = $this->controller()->delete($hoSo->ma_ho_so);

        $this->assertTrue($this->than($phanHoi)['thanh_cong']);
        $this->assertSame(0, Tt12HoSo::count());
        $this->assertSame(0, Tt12Dong::count());
        $this->assertSame(0, Tt12DongThuocPx::count(), 'Dong con khong duoc bo lai mo coi');
        $this->assertSame(0, Tt12Loi::count());
        $this->assertSame(0, Tt12LichSuGui::count());
    }

    /** @test */
    public function xoa_ho_so_xoa_ca_tep_xml_da_ky()
    {
        // Tt12MaHoSo::keTiep() tai dung khoang trong so thu tu trong ngay, nen mot ho so
        // moi cung ngay cung co so mang dung ma vua bi xoa - va SignTt12Job dat ten tep
        // theo ma ho so.
        $hoSo = $this->hoSoDayDu();
        $duongDan = $hoSo->duong_dan_da_ky;

        $this->controller()->delete($hoSo->ma_ho_so);

        $this->assertFalse(Storage::disk('exportTt12')->exists($duongDan));
    }

    /** @test */
    public function khong_xoa_duoc_ho_so_da_duoc_cong_tiep_nhan()
    {
        $hoSo = $this->hoSoDayDu(array('ma_ket_qua' => '200', 'ma_gd' => 'GD1'));

        $phanHoi = $this->controller()->delete($hoSo->ma_ho_so);

        $this->assertSame(422, $phanHoi->getStatusCode());
        $this->assertSame(1, Tt12HoSo::count());
        $this->assertSame(1, Tt12DongThuocPx::count());
    }

    /** @test */
    public function xoa_ho_so_khong_ton_tai_tra_404()
    {
        $phanHoi = $this->controller()->delete('KHONG_CO');

        $this->assertSame(404, $phanHoi->getStatusCode());
    }

    // -- co loi nap tren dong danh sach ----------------------------------------------

    /** @test */
    public function dong_danh_sach_mang_co_loi_nap()
    {
        // Tt12Importer CO Y giu lai ho so do dang kem import_error de nguoi dung nhin thay
        // va xoa. Truoc day khong man hinh nao in cot nay ra: nguoi dung thay mot ho so 0
        // dong, "Chua kiem", khong ky duoc, khong mot chu giai thich.
        $this->hoSoDayDu(array(
            'ma_ho_so' => 'TT12_MAU_05_01929_20260825_002',
            'import_error' => 'Cột TEN_DICH_VU vượt độ dài cột',
        ));

        $phanHoi = $this->controller()->fetchData(Request::create('/x', 'GET'));
        $than = $this->than($phanHoi);

        $this->assertSame(1, $than['data'][0]['co_loi_nap']);
    }

    /** @test */
    public function ho_so_nap_binh_thuong_thi_co_loi_nap_bang_khong()
    {
        $this->hoSoDayDu(array('ma_ho_so' => 'TT12_MAU_05_01929_20260825_003'));

        $phanHoi = $this->controller()->fetchData(Request::create('/x', 'GET'));
        $than = $this->than($phanHoi);

        $this->assertSame(0, $than['data'][0]['co_loi_nap']);
    }

    // -- dong bo lai / kiem lai ------------------------------------------------------

    private function yeuCauJson()
    {
        $r = Request::create('/x', 'POST');
        $r->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $r;
    }

    private function hoSoMau01DaTiepNhan(array $ghiDe = array())
    {
        $hoSo = Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
            'is_signed' => true, 'ma_ket_qua' => '200', 'ma_gd' => 'GD1',
        ), $ghiDe));

        Tt12Dong::create(array(
            'ho_so_id' => $hoSo->id, 'stt' => 1,
            'du_lieu' => array(
                'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Kham benh',
                'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
                'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
                'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            ),
        ));

        return $hoSo;
    }

    /** @test */
    public function dong_bo_lai_ghi_duoc_danh_muc_cho_ho_so_ket_o_dong_bo_at_rong()
    {
        // Kich ban that: SubmitTt12Job da commit ma_ket_qua = 200 roi dongBo() nem giua
        // chung. Job thu lai se thay DA_TIEP_NHAN va return som, nen khong duong nao chay
        // lai dong bo duoc nua.
        $this->dungBangDanhMucTt12();

        $hoSo = $this->hoSoMau01DaTiepNhan();

        $phanHoi = $this->controller()->dongBoLai($this->yeuCauJson(), $hoSo->ma_ho_so);

        $this->assertSame(200, $phanHoi->getStatusCode());
        $this->assertSame(1, DB::table('department_bed_catalogs')->count());
        $this->assertNotNull($hoSo->fresh()->dong_bo_at);
    }

    /** @test */
    public function dong_bo_lai_tu_choi_ho_so_chua_duoc_tiep_nhan()
    {
        // Danh muc la nguon cho buoc kiem XML3176 va giam dinh doi chieu voi chinh ban
        // BHXH da nhan. Ghi truoc khi cong nhan la kiem theo mot ban khong ton tai.
        $this->dungBangDanhMucTt12();

        $hoSo = $this->hoSoMau01DaTiepNhan(array('ma_ket_qua' => '205', 'ma_gd' => null));

        $phanHoi = $this->controller()->dongBoLai($this->yeuCauJson(), $hoSo->ma_ho_so);

        $this->assertSame(422, $phanHoi->getStatusCode());
        $this->assertSame(0, DB::table('department_bed_catalogs')->count());
        $this->assertNull($hoSo->fresh()->dong_bo_at);
    }

    /** @test */
    public function dong_bo_lai_ho_so_khong_ton_tai_thi_bao_loi()
    {
        $phanHoi = $this->controller()->dongBoLai($this->yeuCauJson(), 'KHONG_CO');

        $this->assertSame(422, $phanHoi->getStatusCode());
    }

    /** @test */
    public function kiem_lai_chay_duoc_cho_ho_so_ket_o_CHUA_KIEM()
    {
        // Hang doi tat hoac tries het thi CheckTt12Job khong bao gio chay, va ho so nam
        // mai o "Chua kiem" - khong ky duoc, khong co nut nao chay lai.
        $hoSo = $this->hoSoMau01DaTiepNhan(array(
            'checked_at' => null, 'so_loi' => 0, 'is_signed' => false,
            'ma_ket_qua' => null, 'ma_gd' => null,
        ));

        $phanHoi = $this->controller()->kiemLai($this->yeuCauJson(), $hoSo->ma_ho_so);

        $this->assertSame(200, $phanHoi->getStatusCode());
        $this->assertNotNull($hoSo->fresh()->checked_at, 'Hang doi sync nen job chay ngay');
    }

    /** @test */
    public function kiem_lai_tu_choi_ho_so_DA_KY_du_chua_gui()
    {
        // Tep XML da ky nam nguyen tren dia va mang con so cua lan kiem LUC KY. Kiem lai co
        // the cho so_loi khac, va khi do hai con so noi hai dieu khac nhau ve cung mot ho
        // so.
        $hoSo = $this->hoSoMau01DaTiepNhan(array(
            'checked_at' => null, 'so_loi' => 0,
            'is_signed' => true, 'signed_at' => '2026-08-25 10:05:00',
            'ma_ket_qua' => null, 'ma_gd' => null,
        ));

        $phanHoi = $this->controller()->kiemLai($this->yeuCauJson(), $hoSo->ma_ho_so);

        $this->assertSame(422, $phanHoi->getStatusCode());
        $this->assertContains('đã được ký số', $this->than($phanHoi)['thong_diep']);
        $this->assertNull($hoSo->fresh()->checked_at, 'Khong duoc day job kiem');
    }

    /** @test */
    public function kiem_lai_tu_choi_ho_so_da_duoc_cong_tiep_nhan()
    {
        // Kiem lai ghi de checked_at va so_loi. Lam viec do sau khi cong da nhan la sua
        // dau vet doi soat cua mot ho so khong con sua duoc nua.
        $hoSo = $this->hoSoMau01DaTiepNhan();

        $phanHoi = $this->controller()->kiemLai($this->yeuCauJson(), $hoSo->ma_ho_so);

        $this->assertSame(422, $phanHoi->getStatusCode());
    }
}
