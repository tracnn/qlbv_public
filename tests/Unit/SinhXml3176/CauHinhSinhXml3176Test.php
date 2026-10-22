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

        $this->assertSame(50, $ch->kichThuocLo());
        $this->assertSame(60, $ch->nghiGiay());
        $this->assertSame(10, $ch->chongLanPhut());
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
}
