<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Bon ly do "chua gui" la BON chuyen khac nhau, va nguoi van hanh phai phan biet duoc:
 * chua ky thi di ky, con loi thi di sua, chuc nang gui dang tat thi bao quan tri, con cong
 * tu choi thi phai doc ma loi. Gop lai thanh "chua gui" la de nguoi ta ngoi cho mot ho so
 * vinh vien khong bao gio duoc gui.
 */
class CtdtTrangThaiGuiTest extends TestCase
{
    private function hoSo(array $thuocTinh)
    {
        $hoSo = new CtdtHoSo();

        foreach ($thuocTinh as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function con_loi_chan_duoc_bao_truoc_moi_thu()
    {
        // Ho so con loi thi du co ky cung khong gui duoc - bao ly do gan nhat truoc.
        $hoSo = $this->hoSo(['so_loi' => 3, 'is_signed' => false]);

        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function chua_ky_khi_khong_con_loi()
    {
        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => false]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_KY, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function chuc_nang_gui_dang_tat()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::GUI_TAT, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function da_gui_thanh_cong()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '200',
        ]);

        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function cong_tu_choi_khi_ma_ket_qua_khac_200()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '205',
        ]);

        $this->assertSame(CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ma_ket_qua_so_200_cung_duoc_coi_la_thanh_cong()
    {
        // Khoa mang trong config bi PHP ep thanh int, va cong co the tra ve so. So sanh
        // nghiem ngat voi chuoi '200' se coi mot ho so THANH CONG la bi tu choi.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => 200,
        ]);

        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function da_ky_gui_dang_bat_nhung_chua_gui()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function is_signed_kieu_so_1_van_duoc_coi_la_da_ky()
    {
        // MySQL tinyint(1) doc ve dang 0/1. So sanh === true se coi moi ho so la chua ky.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => 1]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function moi_trang_thai_deu_co_nhan_tieng_viet()
    {
        $ma = [
            CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::CHUA_KY, CtdtTrangThaiGui::GUI_TAT,
            CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::CHUA_GUI,
        ];

        $this->assertCount(6, array_unique($ma), 'Sau hang phai khac nhau');

        foreach ($ma as $m) {
            $this->assertNotEmpty(CtdtTrangThaiGui::nhan($m), 'Thieu nhan cho ' . $m);
        }
    }

    /** @test */
    public function nhan_cua_ma_la_khong_nem()
    {
        $this->assertNotEmpty(CtdtTrangThaiGui::nhan('khong_ton_tai'));
    }

    /** @test */
    public function ket_qua_tu_cong_thang_cau_hinh_dang_tat()
    {
        // Kịch bản: quản trị tắt submit_enabled sau khi hồ sơ đã gửi xong và có ma_ket_qua.
        // Nhánh kiểm ma_ket_qua PHẢI đứng TRƯỚC nhánh kiểm cấu hình. Nếu không, danh sách sẽ
        // hiện "Chức năng gửi đang tắt" cho một hồ sơ ĐÃ GỬI THÀNH CÔNG, làm nhầm người dùng.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '200',
        ]);

        $this->assertSame(
            CtdtTrangThaiGui::DA_GUI,
            CtdtTrangThaiGui::cua($hoSo),
            'Hồ sơ đã gửi xong (có ma_ket_qua = 200) phải báo DA_GUI, không phải GUI_TAT, dù cấu hình hiện đang tắt'
        );
    }

    /** @test */
    public function cong_tu_choi_thang_cau_hinh_dang_tat()
    {
        // Tương tự: nếu cấu hình bị tắt nhưng cổng từ chối (ma_ket_qua != 200), vẫn phải báo
        // CONG_TU_CHOI, không phải GUI_TAT, để người dùng biết cần hành động gì.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '205',
        ]);

        $this->assertSame(
            CtdtTrangThaiGui::CONG_TU_CHOI,
            CtdtTrangThaiGui::cua($hoSo),
            'Hồ sơ bị cổng từ chối (ma_ket_qua = 205) phải báo CONG_TU_CHOI, không phải GUI_TAT'
        );
    }
}
