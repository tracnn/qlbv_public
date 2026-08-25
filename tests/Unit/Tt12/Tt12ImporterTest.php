<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12DongThuocPx;
use App\Services\Tt12\Tt12Importer;
use App\Services\Tt12\Tt12MaHoSo;

class Tt12ImporterTest extends TestCase
{
    use DungBangTt12Sqlite;

    /** @var string thu muc tam chua tep .xlsx dung trong test */
    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tt12-test-' . uniqid();
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

    /**
     * Ghi mot tep .xlsx that tu mang hai chieu.
     *
     * Dung tep THAT chu khong gia lap Excel::import: cai ta muon kiem chinh la viec doc
     * duoc tep nguoi dung tai len, va mot ban gia lap se bo qua dung phan de sai nhat
     * (o so bi Excel tra ve dang float, o ngay bi doi thanh doi tuong ngay thang).
     */
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
        return ['STT', 'MA_KHOA', 'TEN_KHOA', 'BAN_KHAM', 'GIUONG_PD', 'GIUONG_TK',
                'GIUONG_HSTC', 'GIUONG_HSCC', 'TU_NGAY', 'DEN_NGAY', 'MA_CSKCB'];
    }

    /** @test */
    public function nap_duoc_mot_tep_mau_01_va_nhan_dien_dung_mau()
    {
        $tep = $this->ghiXlsx('m1.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 0, 0, 0, 0, '20260101', '', '01929'],
            [2, 'K02', 'Nội tổng hợp', 0, 40, 42, 5, 3, '20260101', '', '01929'],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertTrue($ketQua->thanhCong(), 'Loi: ' . $ketQua->loi());
        $this->assertSame('MAU_01', $ketQua->mau());
        $this->assertSame(2, $ketQua->soDong());

        $hoSo = Tt12HoSo::where('ma_ho_so', $ketQua->maHoSo())->first();

        $this->assertNotNull($hoSo);
        $this->assertSame('MAU_01', $hoSo->mau);
        $this->assertSame('70', $hoSo->loai_hs);
        $this->assertSame('01929', $hoSo->ma_cskcb);
        $this->assertSame(2, (int) $hoSo->so_dong);
        $this->assertNotEmpty($hoSo->id_danh_sach, 'Phai sinh id_danh_sach ngay luc nap');
        $this->assertNotNull($hoSo->imported_at);
    }

    /** @test */
    public function du_lieu_dong_giu_nguyen_van_theo_ten_the()
    {
        $tep = $this->ghiXlsx('m1.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh & Cấp cứu', 3, 0, 0, 0, 0, '20260101', '', '01929'],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);
        $dong = Tt12Dong::first();

        $this->assertSame('K01', $dong->du_lieu['MA_KHOA']);
        $this->assertSame('Khám bệnh & Cấp cứu', $dong->du_lieu['TEN_KHOA']);
        $this->assertSame('20260101', $dong->du_lieu['TU_NGAY']);
        $this->assertSame('', $dong->du_lieu['DEN_NGAY']);
        $this->assertSame(1, $dong->stt);
    }

    /** @test */
    public function o_so_khong_bien_thanh_dang_thap_phan()
    {
        // Excel tra ve o so duoi dang float. Noi thang float vao XML cho ra '3.0' thay vi
        // '3', va cong tra 205 vi BAN_KHAM khai la kieu So 3 ky tu.
        $tep = $this->ghiXlsx('m1.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 40, 42, 5, 3, '20260101', '', '01929'],
        ]);

        (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);
        $dong = Tt12Dong::first();

        $this->assertSame('3', $dong->du_lieu['BAN_KHAM']);
        $this->assertSame('40', $dong->du_lieu['GIUONG_PD']);
    }

    /** @test */
    public function tep_khong_khop_mau_nao_bi_tu_choi_ma_khong_tao_ho_so()
    {
        $tep = $this->ghiXlsx('la.xlsx', [
            ['COT_A', 'COT_B'],
            ['x', 'y'],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertFalse($ketQua->thanhCong());
        $this->assertContains('không nhận diện', $ketQua->loi());
        $this->assertSame(0, Tt12HoSo::count(), 'Khong duoc tao ho so rong');
    }

    /** @test */
    public function tep_khong_co_dong_du_lieu_nao_bi_tu_choi()
    {
        $tep = $this->ghiXlsx('rong.xlsx', [$this->headerMau01()]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertFalse($ketQua->thanhCong());
        $this->assertSame(0, Tt12HoSo::count());
    }

    /** @test */
    public function nap_lai_cung_mot_tep_tao_ho_so_MOI_chu_khong_ghi_de()
    {
        // Moi lan nap la mot lan GUI khac, se co MaGD khac. Ghi de len ho so cu se lam
        // mat dau vet lan gui truoc.
        $tep = $this->ghiXlsx('m1.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 0, 0, 0, 0, '20260101', '', '01929'],
        ]);

        $a = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);
        $b = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertTrue($a->thanhCong());
        $this->assertTrue($b->thanhCong());
        $this->assertNotSame($a->maHoSo(), $b->maHoSo());
        $this->assertSame(2, Tt12HoSo::count());
    }

    /** @test */
    public function mau_05_tach_duoc_bang_con_thuoc_phong_xa()
    {
        $header = array_merge(
            ['STT', 'MA_DICH_VU', 'TEN_DICH_VU', 'TEN_DVKT_GIA', 'DON_GIA', 'QUY_TRINH',
             'SO_LUONG_CGKT', 'CSKCB_CGKT', 'CSKCB_CLS', 'QD_DVKT', 'QD_PD_GIA', 'GHI_CHU',
             'TU_NGAY', 'DEN_NGAY', 'MA_CSKCB', 'GIA_THANH_TOAN'],
            ['THUOCPX_STT', 'THUOCPX_MA_THUOC', 'THUOCPX_TEN_THUOC', 'THUOCPX_SO_DANG_KY',
             'THUOCPX_DON_VI_TINH', 'THUOCPX_TT_THAU', 'THUOCPX_DON_GIA_THUOC',
             'THUOCPX_DM_NSX_CDD', 'THUOCPX_DM_THUCTE_CDD', 'THUOCPX_LIEU_BQ_PX',
             'THUOCPX_TL_THUCTE_BQ_PX', 'THUOCPX_THANH_TIEN_THUOC']
        );

        $coThuoc = [1, 'DV01', 'Xạ hình tuyến giáp', '', 500000, 'QT01', '', '', '', 'QD1',
                    'QD2', '', '20260101', '', '01929', 500000,
                    1, 'TPX01', 'Tc-99m', 'SDK1', 'mCi', 'TT1', 120000, 10, 9, 5, 4, 480000];

        $khongThuoc = [2, 'DV02', 'Siêu âm ổ bụng', '', 100000, 'QT02', '', '', '', 'QD1',
                       'QD2', '', '20260101', '', '01929', 100000,
                       '', '', '', '', '', '', '', '', '', '', '', ''];

        $tep = $this->ghiXlsx('m5.xlsx', [$header, $coThuoc, $khongThuoc]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertTrue($ketQua->thanhCong(), 'Loi: ' . $ketQua->loi());
        $this->assertSame('MAU_05', $ketQua->mau());
        $this->assertSame(2, $ketQua->soDong());

        $this->assertSame(1, Tt12DongThuocPx::count(), 'Chi dong co du lieu moi sinh bang con');

        $con = Tt12DongThuocPx::first();
        $this->assertSame('TPX01', $con->ma_thuoc);
        $this->assertSame('480000', (string) $con->thanh_tien_thuoc);

        $dongCoThuoc = Tt12Dong::where('stt', 1)->first();
        $this->assertSame($dongCoThuoc->id, $con->dong_id);

        // Cot bang con KHONG duoc lan vao du_lieu cua dong cha: chung se dung ra XML o
        // nhanh DS_THUOCPX, in them mot lan o dong cha la XML sai cau truc.
        $this->assertArrayNotHasKey('THUOCPX_MA_THUOC', $dongCoThuoc->du_lieu);
        $this->assertArrayNotHasKey('MA_THUOC', $dongCoThuoc->du_lieu);
    }

    /** @test */
    public function tep_co_dong_mang_ma_co_so_KHAC_thi_dung_ngay_va_khong_tao_ho_so()
    {
        // Mot ho so TT12 duoc gui bang MOT token cua MOT co so. Tep lan 01929 va 37470 la
        // tep khong co cach gui nao dung.
        $tep = $this->ghiXlsx('lan.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 0, 0, 0, 0, '20260101', '', '01929'],
            [2, 'K02', 'Nội tổng hợp', 0, 40, 42, 5, 3, '20260101', '', '37470'],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertFalse($ketQua->thanhCong());
        $this->assertContains('37470', $ketQua->loi());
        $this->assertContains('dòng 2', $ketQua->loi(), 'Phai chi ro dong nao lech');

        $this->assertSame(0, Tt12HoSo::count(), 'Khong duoc de lai ho so do dang');
        $this->assertSame(0, Tt12Dong::count(), 'Khong duoc de lai dong mo coi');
    }

    /** @test */
    public function dong_lech_o_lo_thu_hai_cung_xoa_sach_ho_so()
    {
        // Tep lon duoc doc theo lo; dong lech co the nam o lo thu hai, khi lo dau da ghi
        // xuong CSDL roi. Phai don sach ca phan da ghi.
        $hang = [$this->headerMau01()];

        for ($i = 1; $i <= 30; $i++) {
            $hang[] = [$i, 'K' . $i, 'Khoa ' . $i, 1, 0, 0, 0, 0, '20260101', '',
                       $i === 30 ? '37470' : '01929'];
        }

        $tep = $this->ghiXlsx('lo2.xlsx', $hang);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertFalse($ketQua->thanhCong());
        $this->assertSame(0, Tt12HoSo::count());
        $this->assertSame(0, Tt12Dong::count());
    }

    /** @test */
    public function o_MA_CSKCB_de_trong_duoc_dien_theo_o_chon_chu_khong_bi_coi_la_lech()
    {
        $tep = $this->ghiXlsx('trong.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 0, 0, 0, 0, '20260101', '', ''],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '37470']);

        $this->assertTrue($ketQua->thanhCong(), 'Loi: ' . $ketQua->loi());
        $this->assertSame('37470', Tt12Dong::first()->du_lieu['MA_CSKCB']);
        $this->assertSame(1, $ketQua->soODienThem(), 'Phai bao lai so o da tu dien');
    }

    /** @test */
    public function ma_co_so_co_khoang_trang_thua_van_duoc_coi_la_trung()
    {
        // Nguoi dung copy tu he thong khac vao Excel rat hay keo theo khoang trang.
        $tep = $this->ghiXlsx('trang.xlsx', [
            $this->headerMau01(),
            [1, 'K01', 'Khám bệnh', 3, 0, 0, 0, 0, '20260101', '', ' 01929 '],
        ]);

        $ketQua = (new Tt12Importer())->nhapTuTep($tep, ['ma_cskcb' => '01929']);

        $this->assertTrue($ketQua->thanhCong(), 'Loi: ' . $ketQua->loi());
    }

    /** @test */
    public function do_sach_xoa_ca_dong_con_thuoc_phong_xa_khong_de_mo_coi()
    {
        // Kich ban that: tep MAU_05 dai hon mot lo, lo dau ghi ca Tt12Dong lan
        // Tt12DongThuocPx, lo sau lech MA_CSKCB nen phai huy toan bo. Dung Reflection goi
        // thang doSach() thay vi sinh tep 5.001 dong (cham va khong can thiet) - cai can
        // kiem la BAN THAN ham don dep, khong phai duong di CatalogChunkImport chia lo.
        $hoSo = Tt12HoSo::create([
            'ma_ho_so' => 'TT12_MAU_05_01929_20260825_001',
            'mau'      => 'MAU_05',
            'loai_hs'  => '72',
            'ma_cskcb' => '01929',
        ]);

        $dong = Tt12Dong::create([
            'ho_so_id' => $hoSo->id,
            'stt'      => 1,
            'du_lieu'  => ['MA_DICH_VU' => 'DV01'],
        ]);

        Tt12DongThuocPx::create([
            'dong_id'  => $dong->id,
            'stt'      => 1,
            'ma_thuoc' => 'TPX01',
        ]);

        $importer = new Tt12Importer();
        $doSach = new \ReflectionMethod($importer, 'doSach');
        $doSach->setAccessible(true);
        $doSach->invoke($importer, $hoSo);

        $this->assertSame(0, Tt12HoSo::count());
        $this->assertSame(0, Tt12Dong::count());
        $this->assertSame(0, Tt12DongThuocPx::count(), 'Dong con thuoc phong xa khong duoc bo lai mo coi');
    }

    /** @test */
    public function ma_ho_so_gom_mau_ma_co_so_va_ngay()
    {
        $ma = Tt12MaHoSo::sinh('MAU_03', '01929', \Carbon\Carbon::create(2026, 8, 25), 7);

        $this->assertSame('TT12_MAU_03_01929_20260825_007', $ma);
    }
}
