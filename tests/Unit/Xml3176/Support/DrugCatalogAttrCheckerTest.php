<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\DrugCatalogAttrChecker;
use Tests\TestCase;

class DrugCatalogAttrCheckerTest extends TestCase
{
    /** @test */
    public function khong_lech_khi_moi_thuoc_tinh_khop()
    {
        $xml = ['duong_dung' => '1', 'dang_bao_che' => 'Viên nén', 'don_vi_tinh' => 'Viên'];
        $dm  = ['ma_duong_dung' => '1', 'duong_dung' => 'Uống', 'dang_bao_che' => 'viên  nén', 'don_vi_tinh' => 'viên'];
        $this->assertEquals([], DrugCatalogAttrChecker::lech($xml, $dm));
    }

    /** @test */
    public function duong_dung_khop_theo_ten_khi_khong_khop_ma()
    {
        // xml lưu TÊN đường dùng, danh mục có cả mã lẫn tên → khớp theo tên
        $xml = ['duong_dung' => 'Uống', 'dang_bao_che' => 'V', 'don_vi_tinh' => 'V'];
        $dm  = ['ma_duong_dung' => '1', 'duong_dung' => 'uống', 'dang_bao_che' => 'v', 'don_vi_tinh' => 'v'];
        $this->assertNotContains('DUONG_DUNG', DrugCatalogAttrChecker::lech($xml, $dm));
    }

    /** @test */
    public function duong_dung_lech_khi_khong_khop_ca_ma_lan_ten()
    {
        $xml = ['duong_dung' => '9', 'dang_bao_che' => 'V', 'don_vi_tinh' => 'V'];
        $dm  = ['ma_duong_dung' => '1', 'duong_dung' => 'Uống', 'dang_bao_che' => 'V', 'don_vi_tinh' => 'V'];
        $this->assertContains('DUONG_DUNG', DrugCatalogAttrChecker::lech($xml, $dm));
    }

    /** @test */
    public function dang_bao_che_va_don_vi_tinh_lech_khong_phan_biet_hoa_thuong()
    {
        $xml = ['duong_dung' => '1', 'dang_bao_che' => 'Viên nén', 'don_vi_tinh' => 'Ống'];
        $dm  = ['ma_duong_dung' => '1', 'duong_dung' => 'Uống', 'dang_bao_che' => 'Dung dịch', 'don_vi_tinh' => 'Viên'];
        $lech = DrugCatalogAttrChecker::lech($xml, $dm);
        $this->assertContains('DANG_BAO_CHE', $lech);
        $this->assertContains('DON_VI_TINH', $lech);
    }

    /** @test */
    public function bo_qua_khi_truong_xml_rong()
    {
        $xml = ['duong_dung' => '', 'dang_bao_che' => '', 'don_vi_tinh' => ''];
        $dm  = ['ma_duong_dung' => '1', 'duong_dung' => 'Uống', 'dang_bao_che' => 'Viên', 'don_vi_tinh' => 'Viên'];
        $this->assertEquals([], DrugCatalogAttrChecker::lech($xml, $dm));
    }

    /** @test */
    public function bo_qua_khi_danh_muc_thieu_thuoc_tinh()
    {
        // Danh mục nhập thiếu → không đủ căn cứ để báo lỗi
        $xml = ['duong_dung' => '9', 'dang_bao_che' => 'X', 'don_vi_tinh' => 'Y'];
        $dm  = ['ma_duong_dung' => '', 'duong_dung' => '', 'dang_bao_che' => '', 'don_vi_tinh' => ''];
        $this->assertEquals([], DrugCatalogAttrChecker::lech($xml, $dm));
    }
}
