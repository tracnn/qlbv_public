<?php

namespace Tests\Unit\SinhXml3176;

use Tests\TestCase;
use App\Services\SinhXml3176\CauHinhSinhXml3176;

class CauHinhSinhXml3176Test extends TestCase
{
    /** @test */
    public function test_thieu_khoi_thi_tat()
    {
        config(['organization.sinh_xml3176' => null]);

        $this->assertFalse(CauHinhSinhXml3176::tuConfig()->batBat());
    }

    /** @test */
    public function test_gop_mac_dinh()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true]]);
        $ch = CauHinhSinhXml3176::tuConfig();

        $this->assertTrue($ch->batBat());
        $this->assertFalse($ch->thongTuyen());
        $this->assertFalse($ch->ghiThuMucThat());
        $this->assertSame('HISPro', $ch->ketNoiHis());
        $this->assertSame('D:\XML\SinhXml3176Thu', $ch->thuMuc('chinh_thuc'));
        $this->assertSame('D:\XML\SinhXml3176Thu', $ch->thuMuc(CauHinhSinhXml3176::THONG_TUYEN));
        $this->assertSame([], $ch->doiTuongBn());
        $this->assertSame([], $ch->coSo());
        $this->assertSame([], $ch->doiTuongTt());
        $this->assertSame([], $ch->dienDieuTri());
    }

    /** @test */
    public function test_thu_muc_that()
    {
        config(['organization.sinh_xml3176' => [
            'enabled' => true,
            'ghi_thu_muc_that' => true,
            'thu_muc' => 'X',
            'thu_muc_tt' => 'Y',
        ]]);
        $ch = CauHinhSinhXml3176::tuConfig();

        $this->assertSame('X', $ch->thuMuc('chinh_thuc'));
        $this->assertSame('Y', $ch->thuMuc('thong_tuyen'));
    }

    /** @test */
    public function test_mang_ma_chuan_hoa()
    {
        config(['organization.sinh_xml3176' => [
            'enabled' => true,
            'co_so' => [' 01929 ', '', '37470'],
        ]]);

        $this->assertSame(['01929', '37470'], CauHinhSinhXml3176::tuConfig()->coSo());
    }

    /** @test */
    public function test_tham_so_ky_thuat_tu_config_rieng()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true]]);
        $ch = CauHinhSinhXml3176::tuConfig();

        $this->assertSame(5, $ch->kichThuocLo());
        $this->assertSame('1024M', $ch->memoryLimit());
        $this->assertSame(60, $ch->nghiGiay());
        $this->assertSame(10, $ch->chongLanPhut());
    }
    public function test_cua_so_ra_vien_mac_dinh_60()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true], 'sinh_xml3176' => []]);
        $this->assertSame(60, CauHinhSinhXml3176::tuConfig()->cuaSoRaVienNgay());

        config(['sinh_xml3176' => ['cua_so_ra_vien_ngay' => 30]]);
        $this->assertSame(30, CauHinhSinhXml3176::tuConfig()->cuaSoRaVienNgay());

        config(['sinh_xml3176' => ['cua_so_ra_vien_ngay' => 0]]);
        $this->assertSame(1, CauHinhSinhXml3176::tuConfig()->cuaSoRaVienNgay());
    }

    /** @test */
    public function test_ghi_thu_muc_that_chi_bat_khi_dung_true()
    {
        foreach (['false', 1, '1', 'true', 'yes', [true]] as $gt) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'ghi_thu_muc_that' => $gt, 'thu_muc' => 'X']]);
            $ch = CauHinhSinhXml3176::tuConfig();
            $this->assertFalse($ch->ghiThuMucThat(), 'Gia tri ' . var_export($gt, true) . ' khong duoc bat ghi that');
            $this->assertSame('D:\XML\SinhXml3176Thu', $ch->thuMuc('chinh_thuc'));
        }

        config(['organization.sinh_xml3176' => ['enabled' => true, 'ghi_thu_muc_that' => true]]);
        $this->assertTrue(CauHinhSinhXml3176::tuConfig()->ghiThuMucThat());
    }

    /** @test */
    public function test_mac_dinh_ky_thuat_khi_thieu_config()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true], 'sinh_xml3176' => ['memory_limit' => null, 'kich_thuoc_lo' => null]]);
        $ch = CauHinhSinhXml3176::tuConfig();

        $this->assertSame(5, $ch->kichThuocLo());
        $this->assertSame('1024M', $ch->memoryLimit());

        config(['sinh_xml3176' => ['memory_limit' => '1G', 'kich_thuoc_lo' => 5]]);
        $ch = CauHinhSinhXml3176::tuConfig();
        $this->assertSame('1G', $ch->memoryLimit());
        $this->assertSame(5, $ch->kichThuocLo());
    }

    /** @test */
    public function test_null_khong_de_len_mac_dinh()
    {
        config(['organization.sinh_xml3176' => [
            'enabled' => true, 'thu_muc_thu' => null, 'thong_tuyen' => null, 'co_so' => null,
        ]]);
        $ch = CauHinhSinhXml3176::tuConfig();

        $this->assertSame('D:\XML\SinhXml3176Thu', $ch->thuMuc('chinh_thuc'));
        $this->assertFalse($ch->thongTuyen());
        $this->assertSame([], $ch->coSo());
        $this->assertSame([], $ch->loiCauHinh());
    }

    /** @test */
    public function test_ket_noi_his_rong_la_loi_cau_hinh()
    {
        foreach ([null, '', '   '] as $gt) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'ket_noi_his' => $gt]]);
            $ch = CauHinhSinhXml3176::tuConfig();

            $loi = $ch->loiCauHinh();
            $this->assertCount(1, $loi, var_export($gt, true));
            $this->assertContains('ket_noi_his', $loi[0]);
            try {
                $ch->ketNoiHis();
                $this->fail('ketNoiHis() phai nem khi ket_noi_his = ' . var_export($gt, true));
            } catch (\InvalidArgumentException $e) {
                $this->assertContains('ket_noi_his', $e->getMessage());
            }
        }
    }

    /** @test */
    public function test_thu_muc_thu_trong_cay_quet_la_loi()
    {
        $truongHop = [
            'D:\XML\3176'               => 'thu_muc',
            'd:/xml/3176/'              => 'thu_muc',
            'D:\XML\3176\Thu'           => 'thu_muc',
            'D:\XML\3176TT\\a'      => 'thu_muc_tt',
        ];
        foreach ($truongHop as $thu => $khoa) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_thu' => $thu]]);
            $loi = CauHinhSinhXml3176::tuConfig()->loiCauHinh();
            $this->assertNotEmpty($loi, $thu);
            $this->assertContains('nam trong ' . $khoa . ' (', implode("\n", $loi), $thu);
        }

        // Ten gan giong nhung khong nam trong (D:\XML\3176Thu khong nam trong D:\XML\3176)
        foreach (['D:\XML\3176Thu', 'D:\XML\SinhXml3176Thu', 'D:\XML'] as $thu) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_thu' => $thu]]);
            $this->assertSame([], CauHinhSinhXml3176::tuConfig()->loiCauHinh(), $thu);
        }
    }

    /** @test */
    public function test_thu_muc_luu_trong_thu_muc_quet_la_loi()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true]]);
        $this->assertNull(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        foreach (['', '   ', null] as $rong) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_luu' => $rong]]);
            $this->assertNull(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        }
        config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_luu' => ' D:\XML\Luu ']]);
        $this->assertSame('D:\XML\Luu', CauHinhSinhXml3176::tuConfig()->thuMucLuu());

        $truongHop = [
            'D:\XML\3176'                => 'thu_muc',
            'd:/xml/3176/ngay'           => 'thu_muc',
            'D:\XML\3176TT\a'            => 'thu_muc_tt',
            'D:\XML\SinhXml3176Thu'      => 'thu_muc_thu',
            'D:\XML\SinhXml3176Thu\x'    => 'thu_muc_thu',
        ];
        foreach ($truongHop as $luu => $khoa) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_luu' => $luu]]);
            $loi = implode("\n", CauHinhSinhXml3176::tuConfig()->loiCauHinh());
            $this->assertContains('thu_muc_luu', $loi, $luu);
            $this->assertContains('nam trong ' . $khoa . ' (', $loi, $luu);
        }

        foreach (['D:\XML\SinhXml3176LuuTru', 'D:\XML\3176Luu'] as $luu) {
            config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_luu' => $luu]]);
            $this->assertSame([], CauHinhSinhXml3176::tuConfig()->loiCauHinh(), $luu);
        }
    }

    /** @test */
    public function test_thu_muc_tam()
    {
        config(['organization.sinh_xml3176' => ['enabled' => true]]);
        $ch = CauHinhSinhXml3176::tuConfig();
        $this->assertSame('D:\XML' . DIRECTORY_SEPARATOR . '.sinh_xml3176_tmp', $ch->thuMucTam('D:\XML\3176'));

        config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_tam' => 'D:\Tam']]);
        $ch = CauHinhSinhXml3176::tuConfig();
        $this->assertSame('D:\Tam', $ch->thuMucTam('D:\XML\3176'));
        $this->assertSame([], $ch->loiCauHinh());

        // Thu muc tam trong cay quet, hoac khac o dia
        config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_tam' => 'D:\XML\3176\tmp']]);
        $this->assertContains('Thu muc tam', implode("\n", CauHinhSinhXml3176::tuConfig()->loiCauHinh()));
        config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_tam' => 'E:\Tam']]);
        $this->assertContains('khac o dia', implode("\n", CauHinhSinhXml3176::tuConfig()->loiCauHinh()));

        // thu_muc_tt nam trong thu_muc: thu muc tam mac dinh cua thu_muc_tt roi vao cay quet cua thu_muc
        config(['organization.sinh_xml3176' => ['enabled' => true, 'thu_muc_tt' => 'D:\XML\3176\TT']]);
        $this->assertContains('Thu muc tam', implode("\n", CauHinhSinhXml3176::tuConfig()->loiCauHinh()));
    }
}
