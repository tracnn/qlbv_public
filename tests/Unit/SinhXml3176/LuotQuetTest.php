<?php

namespace Tests\Unit\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use App\Services\SinhXml3176\Bang\DangKyBang;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\KiemDieuKien;
use App\Services\SinhXml3176\LuotQuet;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use App\Services\SinhXml3176\TheoDoiSinh;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\Support\HoSoHisSinhXml3176;
use Tests\TestCase;

class LuotQuetTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;
    use HoSoHisSinhXml3176;

    const CT = CauHinhSinhXml3176::CHINH_THUC;
    const TT = CauHinhSinhXml3176::THONG_TUYEN;

    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->dungBangHis();
        $this->themDanhMucLuot();
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_luot_' . uniqid();
    }

    protected function tearDown()
    {
        $this->xoaThuMuc($this->thuMuc);
        parent::tearDown();
    }

    private function luot(array $kyThuat = [], TheoDoiSinh $theoDoi = null)
    {
        config([
            'organization.sinh_xml3176' => [
                'enabled' => true, 'ket_noi_his' => 'HISPro', 'thu_muc_thu' => $this->thuMuc,
            ],
            'sinh_xml3176' => array_merge(['kich_thuoc_lo' => 50, 'chong_lan_phut' => 10], $kyThuat),
        ]);
        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $ungVien = new HisUngVien($cauHinh);

        return new LuotQuet($cauHinh, $ungVien, new HisNapHoSo($cauHinh, $ungVien),
            $theoDoi ?: new TheoDoiSinh(), DangKyBang::macDinh(), new KiemDieuKien());
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
        // Ho so khong co thuoc: khong co XML2 trong phong bi
        $this->assertSame(['XML1', 'XML3'], $loai);
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
        $this->assertSame('XML1,XML3', $dong[0]->cac_bang);
        $this->assertNull($dong[0]->ly_do);

        $this->assertSame(20261001110000, (new TheoDoiSinh())->docMoc(self::CT));
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(self::TT));
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
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(self::CT));

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
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(self::CT));
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
        $this->assertSame(0, (new TheoDoiSinh())->docMoc(self::CT));
    }
}
