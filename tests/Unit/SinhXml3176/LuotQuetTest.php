<?php

namespace Tests\Unit\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use App\Models\SinhXml3176\Moc;
use App\Services\SinhXml3176\Bang\DangKyBang;
use App\Services\SinhXml3176\Bang\Xml1Dung;
use App\Services\SinhXml3176\Bang\Xml2Dung;
use App\Services\SinhXml3176\Bang\Xml3Dung;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\KiemDieuKien;
use App\Services\SinhXml3176\LoiGhiTep;
use App\Services\SinhXml3176\LuotQuet;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use App\Services\SinhXml3176\TheoDoiSinh;
use Illuminate\Support\Facades\Log;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\Support\HoSoHisSinhXml3176;
use Tests\TestCase;

class LuotQuetTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;
    use HoSoHisSinhXml3176;

    const CT = CauHinhSinhXml3176::CHINH_THUC;
    const TT = CauHinhSinhXml3176::THONG_TUYEN;
    /** Moc gieo san cho ca hai loai ban (luot dau khi chua co moc chi khoi tao, khong quet) */
    const MOC_GOC = 20260101000000;

    private $goc;
    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->dungBangHis();
        $this->themDanhMucLuot();
        // Thu muc dich nam trong $goc de thu muc tam anh em (.sinh_xml3176_tmp) cung duoc don
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_luot_' . uniqid();
        mkdir($this->goc, 0775, true);
        $this->thuMuc = $this->goc . DIRECTORY_SEPARATOR . 'thu';
        (new TheoDoiSinh())->datMoc(self::CT, self::MOC_GOC);
        (new TheoDoiSinh())->datMoc(self::TT, self::MOC_GOC);
    }

    protected function tearDown()
    {
        $this->xoaThuMuc($this->goc);
        parent::tearDown();
    }

    private function luot(array $kyThuat = [], TheoDoiSinh $theoDoi = null, array $khoi = [], DangKyBang $dangKy = null)
    {
        config([
            'organization.sinh_xml3176' => array_merge([
                'enabled' => true, 'ket_noi_his' => 'HISPro', 'thu_muc_thu' => $this->thuMuc,
            ], $khoi),
            'sinh_xml3176' => array_merge(['kich_thuoc_lo' => 50, 'chong_lan_phut' => 10], $kyThuat),
        ]);
        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $ungVien = new HisUngVien($cauHinh);
        $ungVien->datHomNay(new \DateTime('2026-10-10'));

        return new LuotQuet($cauHinh, $ungVien, new HisNapHoSo($cauHinh, $ungVien),
            $theoDoi ?: new TheoDoiSinh(), $dangKy ?: DangKyBang::macDinh(), new KiemDieuKien());
    }

    private function tep()
    {
        $ds = glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') ?: [];
        sort($ds);

        return array_map('basename', $ds);
    }

    private function kq($ungVien, $daGhi, $biLoai, $loi, $boQua)
    {
        return ['ung_vien' => $ungVien, 'da_ghi' => $daGhi, 'bi_loai' => $biLoai, 'loi' => $loi, 'bo_qua' => $boQua];
    }

    public function test_luot_ghi_tep_va_theo_doi()
    {
        $this->themHoSoLuot(1);

        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 1, 0, 0, 0), $kq);
        $tep = $this->tep();
        $this->assertCount(1, $tep);
        $this->assertRegExp('/^QLBV_\d{14}_HS1\.xml$/', $tep[0]);

        $xml = simplexml_load_file($this->thuMuc . DIRECTORY_SEPARATOR . $tep[0]);
        $this->assertSame('01929', (string) $xml->THONGTINDONVI->MACSKCB);
        $loai = [];
        foreach ($xml->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $f) {
            $loai[] = (string) $f->LOAIHOSO;
            $con = simplexml_load_string(base64_decode((string) $f->NOIDUNGFILE));
            $this->assertNotFalse($con);
        }
        // Ho so khong co thuoc: khong co XML2; dich vu mac dinh la XN (CLS) => co XML4; khong co to dieu tri/kham chinh => khong XML5
        $this->assertSame(['XML1', 'XML3', 'XML4'], $loai);
        // Ten tep cung moc voi NGAYLAP
        $this->assertSame(substr($tep[0], 5, 8), (string) $xml->THONGTINHOSO->NGAYLAP);

        $dong = LanSinh::all();
        $this->assertCount(1, $dong);
        $this->assertSame('da_ghi', $dong[0]->trang_thai);
        $this->assertSame('HS1', $dong[0]->treatment_code);
        $this->assertSame(self::CT, $dong[0]->loai_ban);
        $this->assertEquals(20261001110000, $dong[0]->fee_lock_time);
        $this->assertEquals(20261001100000, $dong[0]->out_time);
        $this->assertSame($tep[0], $dong[0]->ten_tep);
        $this->assertSame($this->thuMuc, $dong[0]->thu_muc);
        $this->assertSame('XML1,XML3,XML4', $dong[0]->cac_bang);
        $this->assertNull($dong[0]->ly_do);

        $this->assertSame(20261001110000, (new TheoDoiSinh())->docMoc(self::CT));
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(self::TT));
    }

    public function test_ho_so_co_cls_va_to_dieu_tri_ghi_xml4_xml5()
    {
        $this->themHoSoLuot(1);
        $this->themToDieuTriLuot(1, 'Benh nhan on dinh');
        $this->themChiSoXnLuot(1, 1);

        $this->luot()->chay(self::CT);

        // Khong gieo thuoc nen khong co XML2
        $this->assertSame('XML1,XML3,XML4,XML5', LanSinh::first()->cac_bang);
    }

    public function test_ho_so_khong_co_cls_khong_co_xml4()
    {
        // Dich vu loai BHYT 3 (khong thuoc CLS/PTTT) => khong co XML4
        $this->themHoSoLuot(1, [], ['tdl_hein_service_type_id' => 3]);

        $this->luot()->chay(self::CT);

        $this->assertSame('XML1,XML3', LanSinh::first()->cac_bang);
    }

    private function loaiTrongTep($ten)
    {
        $xml = simplexml_load_file($this->thuMuc . DIRECTORY_SEPARATOR . $ten);
        $loai = [];
        foreach ($xml->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $f) {
            $loai[] = (string) $f->LOAIHOSO;
        }

        return $loai;
    }

    public function test_ho_so_kham_thuong_chi_xml1_den_5()
    {
        // Review Focus 4: kham ngoai tru binh thuong (khong sinh, khong chuyen, khong hen) => cac lop XML6-15 tra []
        $this->themHoSoLuot(1);
        $this->themToDieuTriLuot(1, 'Benh nhan on dinh');
        $this->themChiSoXnLuot(1, 1);

        $this->luot()->chay(self::CT);

        $tep = $this->tep();
        $this->assertCount(1, $tep);
        $this->assertSame(['XML1', 'XML3', 'XML4', 'XML5'], $this->loaiTrongTep($tep[0]));
        $this->assertSame('XML1,XML3,XML4,XML5', LanSinh::first()->cac_bang);
    }

    public function test_ho_so_chuyen_vien_co_xml13()
    {
        $this->themHoSoLuot(1, ['treatment_end_type_id' => 2, 'out_code' => 'CV12']);

        $this->luot()->chay(self::CT);

        $this->assertSame('da_ghi', LanSinh::first()->trang_thai, (string) LanSinh::first()->ly_do);
        $tep = $this->tep();
        $loai = $this->loaiTrongTep($tep[0]);
        $this->assertContains('XML13', $loai);
        $this->assertNotContains('XML14', $loai);
        $this->assertContains('XML13', explode(',', LanSinh::first()->cac_bang));
        $xml = simplexml_load_file($this->thuMuc . DIRECTORY_SEPARATOR . $tep[0]);
        foreach ($xml->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $f) {
            if ((string) $f->LOAIHOSO === 'XML13') {
                $con = simplexml_load_string(base64_decode((string) $f->NOIDUNGFILE));
                $this->assertSame('CHI_TIEU_GIAYCHUYENTUYEN', $con->getName());
                $this->assertSame('HS1', (string) $con->MA_LK);
            }
        }
    }

    public function test_luot_chong_lan_khong_sinh_lai()
    {
        $this->themHoSoLuot(1);
        $this->luot()->chay(self::CT);

        // Luot sau lui moc 10 phut nen doc lai ho so cu, nhung fee_lock khong doi => bo qua
        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 0, 0, 0, 1), $kq);
        $this->assertCount(1, $this->tep());
        $this->assertSame(1, LanSinh::count());
        $this->assertSame(20261001110000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_khoa_lai_doi_fee_lock_sinh_lai()
    {
        $this->themHoSoLuot(1);
        $this->luot()->chay(self::CT);

        $this->khoaLaiHoSo(1, 20261002090000);
        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 1, 0, 0, 0), $kq);
        $dong = LanSinh::orderBy('id')->get();
        $this->assertCount(2, $dong);
        $this->assertEquals(20261002090000, $dong[1]->fee_lock_time);
        $this->assertSame('da_ghi', $dong[1]->trang_thai);
        $this->assertSame(20261002090000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_mot_ho_so_loi_khong_dung_luot()
    {
        // ICD thieu danh muc khong con lam hong XML1 (giong DLL, xem Xml1DungTest) => ho so giua thieu ngay sinh
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000, 'tdl_patient_dob' => null, 'icd_code' => 'Z99']);
        $this->themHoSoLuot(3, ['fee_lock_time' => 20261001130000]);

        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(3, 2, 0, 1, 0), $kq);
        $this->assertCount(2, $this->tep());
        $loi = LanSinh::where('trang_thai', 'loi')->first();
        $this->assertSame('HS2', $loi->treatment_code);
        $this->assertStringStartsWith('XML1: ', $loi->ly_do);
        $this->assertContains('ngày sinh', $loi->ly_do);
        $this->assertNull($loi->ten_tep);
        $this->assertSame(20261001130000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_ho_so_bi_loai_ghi_ly_do()
    {
        $this->themHoSoLuot(1, ['tdl_patient_commune_code' => null]);

        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 0, 1, 0, 0), $kq);
        $this->assertSame([], $this->tep());
        $dong = LanSinh::first();
        $this->assertSame('bi_loai', $dong->trang_thai);
        $this->assertSame('Hồ sơ thiếu thông tin địa chỉ', $dong->ly_do);
    }

    public function test_thieu_ma_cskcb_thi_loi()
    {
        $this->themHoSoLuot(1, ['branch_id' => 9]);

        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 0, 0, 1, 0), $kq);
        $this->assertSame([], $this->tep());
        $this->assertContains('MACSKCB', LanSinh::first()->ly_do);
    }

    public function test_moc_chi_tien_sau_khi_xong_lo()
    {
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);

        // Ghi theo doi ho so thu hai ném (mat CSDL qlbv): luot dung, moc KHONG duoc tien
        $hong = new class extends TheoDoiSinh {
            public $soLan = 0;

            public function ghi(array $ketQua)
            {
                if (++$this->soLan === 2) {
                    throw new \RuntimeException('mat ket noi');
                }
                parent::ghi($ketQua);
            }
        };

        try {
            $this->luot([], $hong)->chay(self::CT);
            $this->fail('Phai nem RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('mat ket noi', $e->getMessage());
        }
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(self::CT));

        // Luot sau: ho so 1 bo qua, ho so 2 sinh; moc = lon nhat cua lo
        $kq = $this->luot()->chay(self::CT);
        $this->assertSame($this->kq(2, 1, 0, 0, 1), $kq);
        $this->assertSame(20261001120000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_moc_khong_lui_khi_luot_rong()
    {
        (new TheoDoiSinh())->datMoc(self::CT, 20261005000000);

        $this->assertSame($this->kq(0, 0, 0, 0, 0), $this->luot()->chay(self::CT));
        $this->assertSame(20261005000000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_nhieu_ung_vien_hon_kich_thuoc_lo_trong_cua_so_chong_lan()
    {
        // 5 ho so cung fee_lock, lo = 2: phai phan trang keyset, khong doc lai mai trang dau
        foreach ([1, 2, 3, 4, 5] as $id) {
            $this->themHoSoLuot($id, ['fee_lock_time' => 20261001110000]);
        }
        $this->themHoSoLuot(6, ['fee_lock_time' => 20261001110500]);

        $kq = $this->luot(['kich_thuoc_lo' => 2])->chay(self::CT);

        $this->assertSame($this->kq(6, 6, 0, 0, 0), $kq);
        $this->assertCount(6, $this->tep());
        $this->assertSame(20261001110500, (new TheoDoiSinh())->docMoc(self::CT));

        // Luot sau: ca 6 nam trong cua so chong lan 10 phut => doc het, bo qua het, van dung
        $kq = $this->luot(['kich_thuoc_lo' => 2])->chay(self::CT);
        $this->assertSame($this->kq(6, 0, 0, 0, 6), $kq);
        $this->assertSame(6, LanSinh::count());
    }

    public function test_lui_moc_theo_lich()
    {
        $this->assertSame(20260930235500, LuotQuet::luiMoc(20261001000500, 10));
        $this->assertSame(20251231235000, LuotQuet::luiMoc(20260101000000, 10));
        $this->assertSame(20261001000500, LuotQuet::luiMoc(20261001000500, 0));
        $this->assertSame(0, LuotQuet::luiMoc(0, 10));
    }

    public function test_thong_tuyen_ghi_tep_tt()
    {
        $this->themHoSoLuot(1, ['fee_lock_time' => null, 'out_time' => 20261001100000]);

        $kq = $this->luot()->chay(self::TT);

        $this->assertSame($this->kq(1, 1, 0, 0, 0), $kq);
        $tep = $this->tep();
        $this->assertRegExp('/^QLBV_\d{14}_HS1_TT\.xml$/', $tep[0]);
        $this->assertSame(20261001100000, (new TheoDoiSinh())->docMoc(self::TT));
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_chay_cho_ma_bo_qua_can_sinh_khong_dung_moc()
    {
        $this->themHoSoLuot(1);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);
        $this->luot()->chay(self::CT);
        $moc = (new TheoDoiSinh())->docMoc(self::CT);

        $kq = $this->luot()->chayChoMa(['HS1', 'KHONGCO'], self::CT);

        $this->assertSame(1, $kq['ung_vien']);
        $this->assertSame(1, $kq['da_ghi']);
        $this->assertSame(0, $kq['bo_qua']);
        $this->assertSame(['KHONGCO'], $kq['khong_tim_thay']);
        $this->assertSame(2, LanSinh::where('treatment_code', 'HS1')->count());
        $this->assertSame($moc, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_chay_cho_khoang_ngay_gom_hai_dau_phan_trang()
    {
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001000000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261002000000]);
        $this->themHoSoLuot(3, ['fee_lock_time' => 20261002000000]);
        $this->themHoSoLuot(4, ['fee_lock_time' => 20261003000000]);
        $this->themHoSoLuot(5, ['fee_lock_time' => 20261004000000]);

        $kq = $this->luot(['kich_thuoc_lo' => 1])->chayChoKhoangNgay(20261002000000, 20261003000000, self::CT);

        $this->assertSame($this->kq(3, 3, 0, 0, 0), $kq);
        $this->assertSame(['HS2', 'HS3', 'HS4'], LanSinh::orderBy('treatment_code')->pluck('treatment_code')->all());
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_loi_ghi_tep_dung_luot_khong_ghi_theo_doi_khong_tien_moc()
    {
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);
        // Thu muc dich la mot TEP => khong tao duoc thu muc (giong o day / mat quyen ghi)
        file_put_contents($this->thuMuc, 'chan');

        try {
            $this->luot()->chay(self::CT);
            $this->fail('Phai nem LoiGhiTep');
        } catch (LoiGhiTep $e) {
            $this->assertContains($this->thuMuc, $e->getMessage());
        }
        $this->assertSame(0, LanSinh::count());
        $this->assertSame(self::MOC_GOC, (new TheoDoiSinh())->docMoc(self::CT));

        // Sua xong moi truong: luot sau sinh lai ca hai
        unlink($this->thuMuc);
        $this->assertSame($this->kq(2, 2, 0, 0, 0), $this->luot()->chay(self::CT));
        $this->assertSame(20261001120000, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_ho_so_loi_ghi_log_canh_bao()
    {
        $this->themHoSoLuot(1, ['tdl_patient_dob' => null]);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);

        Log::shouldReceive('info');
        Log::shouldReceive('warning')->once()->with(\Mockery::on(function ($thongDiep) {
            return strpos($thongDiep, 'HS1') !== false && strpos($thongDiep, 'XML1: ') !== false;
        }));

        $this->assertSame($this->kq(2, 1, 0, 1, 0), $this->luot()->chay(self::CT));
    }

    public function test_luot_dau_chua_co_moc_chi_khoi_tao_moc_khong_quet_lich_su()
    {
        Moc::query()->delete();
        $this->themHoSoLuot(1, ['fee_lock_time' => 20261001110000]);

        $truoc = (int) (new \DateTime())->format('YmdHis');
        $kq = $this->luot()->chay(self::CT);
        $sau = (int) (new \DateTime())->format('YmdHis');

        $this->assertSame(0, $kq['ung_vien']);
        $this->assertSame(0, $kq['da_ghi']);
        $this->assertSame([], $this->tep());
        $this->assertFalse(is_dir($this->thuMuc));
        $this->assertSame(0, LanSinh::count());
        $this->assertTrue((new TheoDoiSinh())->coMoc(self::CT));
        $moc = (new TheoDoiSinh())->docMoc(self::CT);
        $this->assertGreaterThanOrEqual($truoc, $moc);
        $this->assertLessThanOrEqual($sau, $moc);
        $this->assertSame($moc, $kq['khoi_tao_moc']);
        // Loai ban kia khong bi dong cham
        $this->assertFalse((new TheoDoiSinh())->coMoc(self::TT));

        // Luot sau: chi ho so moi hon moc (ho so cu nam ngoai cua so chong lan 10 phut)
        $moi = (int) (new \DateTime('+1 hour'))->format('YmdHis');
        $this->themHoSoLuot(2, ['fee_lock_time' => $moi]);
        $kq = $this->luot()->chay(self::CT);

        $this->assertSame($this->kq(1, 1, 0, 0, 0), $kq);
        $this->assertSame(['HS2'], LanSinh::pluck('treatment_code')->all());
        $this->assertSame($moi, (new TheoDoiSinh())->docMoc(self::CT));
    }

    public function test_moc_da_luu_bang_0_van_quet_tu_dau()
    {
        // Dong moc = 0 (khac voi chua co dong): quet binh thuong tu dau
        (new TheoDoiSinh())->datMoc(self::CT, 0);
        $this->themHoSoLuot(1);

        $this->assertSame($this->kq(1, 1, 0, 0, 0), $this->luot()->chay(self::CT));
    }

    public function test_tu_choi_thu_muc_that_khi_chua_du_bang()
    {
        $that = $this->goc . DIRECTORY_SEPARATOR . 'that';
        $this->themHoSoLuot(1);
        $khoi = ['ghi_thu_muc_that' => true, 'thu_muc' => $that, 'thu_muc_tt' => $that . 'TT'];

        foreach (['chay', 'chayChoMa', 'chayChoKhoangNgay'] as $ham) {
            try {
                $luot = $this->luot([], null, $khoi, new DangKyBang([new Xml2Dung(), new Xml3Dung(), new Xml1Dung()]));
                if ($ham === 'chay') {
                    $luot->chay(self::CT);
                } elseif ($ham === 'chayChoMa') {
                    $luot->chayChoMa(['HS1'], self::CT);
                } else {
                    $luot->chayChoKhoangNgay(20260101000000, 20261231235959, self::CT);
                }
                $this->fail($ham . ' phai tu choi');
            } catch (\RuntimeException $e) {
                $this->assertContains('chua du 14 bang', $e->getMessage());
                $this->assertContains('XML6', $e->getMessage());
            }
        }
        $this->assertFalse(is_dir($that));
        $this->assertSame(0, LanSinh::count());
    }

    public function test_thu_muc_dich_chi_co_tep_xml()
    {
        $this->themHoSoLuot(1);
        $this->themHoSoLuot(2, ['fee_lock_time' => 20261001120000]);

        $this->luot()->chay(self::CT);

        $tatCa = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->thuMuc, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $tatCa[] = $f->getFilename();
        }
        $this->assertCount(2, $tatCa);
        foreach ($tatCa as $ten) {
            $this->assertRegExp('/^QLBV_\d{14}_HS\d\.xml$/', $ten);
        }
        // Tep tam o thu muc anh em, da rename het
        $this->assertSame([], glob($this->goc . DIRECTORY_SEPARATOR . '.sinh_xml3176_tmp' . DIRECTORY_SEPARATOR . '*'));
    }
}
