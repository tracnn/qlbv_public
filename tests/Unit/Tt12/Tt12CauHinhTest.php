<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Canh dac ta sau mau TT12.
 *
 * VI SAO CAN TEST CHO MOT TEP CAU HINH: loai_hs va ten the la thu duy nhat phan biet
 * sau mau. Go nham 70 thanh 71 thi ho so van duoc cong nhan nhung vao sai danh muc -
 * hong IM LANG, khong co dau hieu gi cho toi luc doi soat.
 */
class Tt12CauHinhTest extends TestCase
{
    /** @return array [ma mau, the danh sach, the dong, loai_hs, duoi url, so cot] */
    public function cacMau()
    {
        return [
            ['MAU_01', 'DANHSACH_DMBOPHANCHUYENMON',    'DMBOPHANCHUYENMON',    '70', 'GuiDanhMuc01_BPCMKBCB', 11],
            ['MAU_02', 'DANHSACH_DMNHANLUCKBCB',        'DMNHANLUCKBCB',        '71', 'GuiDanhMuc02_NLKCB',    24],
            ['MAU_03', 'DANHSACH_DMTHUOCMAUCHEPHAMMAU', 'DMTHUOCMAUCHEPHAMMAU', '10', 'GuiDanhMuc03_DMTHUOC',  37],
            ['MAU_04', 'DSACH_TBYT',                    'DM_TBYT',              '11', 'GuiDanhMuc04_DMVTYT',   26],
            ['MAU_05', 'DANHSACH_DMDICHVUKBCB',         'DMDICHVUKBCB',         '12', 'GuiDanhMuc05_DVKT',     16],
            ['MAU_06', 'DSACH_TBYTTHDV',                'DM_TBYTTHDV',          '72', 'GuiDanhMuc06_DMTBYT',   14],
        ];
    }

    /** @test */
    public function sau_mau_khai_dung_the_loai_hs_url_va_so_cot()
    {
        $dangKy = Tt12MauRegistry::tatCa();
        $this->assertCount(6, $dangKy, 'Phai co dung sau mau');

        foreach ($this->cacMau() as list($ma, $theDs, $theDong, $loaiHs, $duoiUrl, $soCot)) {
            $this->assertArrayHasKey($ma, $dangKy, 'Thieu mau ' . $ma);

            $lop = $dangKy[$ma];
            $this->assertSame($ma,      $lop::ma(),           $ma . ': sai ma');
            $this->assertSame($theDs,   $lop::theDanhSach(),  $ma . ': sai the danh sach');
            $this->assertSame($theDong, $lop::theDong(),      $ma . ': sai the dong');
            $this->assertCount($soCot,  $lop::cot(),          $ma . ': sai so cot');
            $this->assertNotEmpty($lop::ten(), $ma . ': thieu ten hien thi');

            $cauHinh = config('tt12.mau.' . $ma);
            $this->assertInternalType('array', $cauHinh, $ma . ': thieu config');
            $this->assertSame($loaiHs, $cauHinh['loai_hs'], $ma . ': sai loai_hs');
            // Khang dinh DUONG DAN, khong phai URL day du: host nam o
            // organization.BHYT.base_url va doi theo moi truong. Ghim host o day se lam
            // bo test do tren may thu nghiem - dung thu ta muon cau hinh duoc.
            $this->assertStringEndsWith($duoiUrl, $cauHinh['duong_dan'], $ma . ': sai duong dan');
            $this->assertStringStartsWith(
                '/api/DanhMucGW/',
                $cauHinh['duong_dan'],
                $ma . ': duong dan khong tro dung DanhMucGW'
            );
        }
    }

    /** @test */
    public function loai_hs_la_chuoi_khong_phai_so()
    {
        foreach (config('tt12.mau') as $ma => $cauHinh) {
            $this->assertInternalType('string', $cauHinh['loai_hs'], $ma . ': loai_hs phai la chuoi');
        }
    }

    /** @test */
    public function moi_cot_khai_du_the_kieu_max_batbuoc()
    {
        $kieuHopLe = ['so', 'chuoi', 'ngay8'];

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            foreach ($lop::cot() as $cot) {
                $this->assertArrayHasKey('the', $cot, $ma . ': cot thieu khoa the');
                $this->assertContains($cot['kieu'], $kieuHopLe, $ma . '.' . $cot['the'] . ': kieu la');
                $this->assertArrayHasKey('max', $cot, $ma . '.' . $cot['the'] . ': thieu max');
                $this->assertArrayHasKey('bat_buoc', $cot, $ma . '.' . $cot['the'] . ': thieu bat_buoc');
            }
        }
    }

    /** @test */
    public function the_dau_tien_luon_la_stt_va_khong_the_nao_trung_nhau()
    {
        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $ten = $lop::tenThe();
            $this->assertSame('STT', $ten[0], $ma . ': the dau tien phai la STT');
            $this->assertSame(count($ten), count(array_unique($ten)), $ma . ': co the trung nhau');
        }
    }

    /** @test */
    public function chi_mau_05_co_bang_con()
    {
        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            if ($ma === 'MAU_05') {
                $this->assertCount(12, $lop::cotCon(), 'MAU_05: bang con phai co 12 the');
                $this->assertSame('DS_THUOCPX', $lop::theDanhSachCon());
                $this->assertSame('TT_THUOCPX', $lop::theDongCon());
            } else {
                $this->assertSame(array(), $lop::cotCon(), $ma . ': khong duoc co bang con');
            }
        }
    }

    /** @test */
    public function cot_danh_muc_bo_stt_va_ha_chu_thuong()
    {
        // cho() tra ve TEN LOP dang chuoi, nen phai gan vao bien roi moi goi static -
        // PHP khong cho goi static tren mot bieu thuc.
        $lop = Tt12MauRegistry::cho('MAU_01');
        $anhXa = $lop::cotDanhMuc();

        $this->assertArrayNotHasKey('STT', $anhXa, 'STT khong duoc anh xa sang danh muc');
        $this->assertSame('ma_khoa', $anhXa['MA_KHOA']);
        $this->assertSame('ma_cskcb', $anhXa['MA_CSKCB']);
    }

    /** @test */
    public function the_ma_va_the_ten_deu_nam_trong_danh_sach_the()
    {
        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $ten = $lop::tenThe();

            $this->assertContains($lop::theMa(),  $ten, $ma . ': theMa() khong co trong tenThe()');
            $this->assertContains($lop::theTen(), $ten, $ma . ': theTen() khong co trong tenThe()');
            $this->assertNotSame('STT', $lop::theMa(), $ma . ': theMa() khong duoc la STT');
        }

        $lopSau = Tt12MauRegistry::cho('MAU_06');
        $this->assertSame('MA_MAY', $lopSau::theMa(), 'MAU_06 phai ghi de theMa()');
    }

    /** @test */
    public function nhan_dien_mau_tu_hang_tieu_de()
    {
        $header = array('STT', 'MA_KHOA', 'TEN_KHOA', 'BAN_KHAM', 'GIUONG_PD', 'GIUONG_TK',
                        'GIUONG_HSTC', 'GIUONG_HSCC', 'TU_NGAY', 'DEN_NGAY', 'MA_CSKCB');

        $this->assertSame('MAU_01', Tt12MauRegistry::nhanDien($header));
        $this->assertNull(Tt12MauRegistry::nhanDien(array('A', 'B', 'C')));
    }

    /** @test */
    public function cho_nem_khi_mau_khong_co_trong_dang_ky()
    {
        $this->expectException(\InvalidArgumentException::class);

        Tt12MauRegistry::cho('MAU_99');
    }
}
