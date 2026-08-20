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
    /**
     * Mac dinh la ho so DA di qua bo kiem. Test nao muon kich ban "chua kiem" phai noi ro
     * 'checked_at' => null - de khong ai vo tinh viet mot test mo ta hanh vi cua ho so
     * chua kiem trong khi tuong minh dang mo ta ho so sach.
     */
    private function hoSo(array $thuocTinh)
    {
        $thuocTinh = array_merge(['checked_at' => '2026-08-20 08:00:00'], $thuocTinh);

        $hoSo = new CtdtHoSo();

        foreach ($thuocTinh as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function chua_kiem_khong_duoc_coi_la_sach()
    {
        // ĐÂY là hồ sơ mà nếu không có test này, Giai đoạn 4 sẽ ký và GỬI LÊN CỔNG BHXH
        // dù chưa ai kiểm nó: so_loi = 0 vì bộ kiểm CHƯA CHẠY (checked_at = null), không
        // phải vì hồ sơ sạch. Mọi hồ sơ trên một máy chủ chưa cài dịch vụ JobCtdt đều
        // trông y hệt thế này.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'checked_at' => null, 'so_loi' => 0, 'is_signed' => true,
        ]);

        $this->assertSame(
            CtdtTrangThaiGui::CHUA_KIEM,
            CtdtTrangThaiGui::cua($hoSo),
            'Ho so chua di qua bo kiem phai la CHUA_KIEM, khong duoc rot vao CHUA_GUI - '
            . 'CHUA_GUI la cua chan mo cho Giai doan 4 gui len cong BHXH'
        );
    }

    /** @test */
    public function chua_kiem_uu_tien_hon_con_loi_khi_so_loi_cu_con_dong()
    {
        // Luong that (CheckCtdtJob) ghi so_loi va checked_at CUNG MOT LUC, nen ho so
        // checked_at = null VOI so_loi > 0 khong tung xay ra trong du lieu that. Nhung
        // chinh vi khong ai gap no ma thu tu uu tien giua CHUA_KIEM va CON_LOI trong
        // cua() chua bao gio bi canh: dao hai nhanh do cho nhau se khong lam do bat ky
        // test nao khac.
        //
        // Neu sau nay co duong nao reset checked_at (vi du nap lai ho so) ma quen reset
        // so_loi, thu tu nay la thu quyet dinh man hinh hien "Chua kiem" hay "Con loi
        // chan". "Chua kiem" moi dung, vi con so so_loi luc do da CU - no la ket qua cua
        // lan kiem TRUOC, khong phai ket qua kiem cua du lieu hien tai.
        $hoSo = $this->hoSo([
            'checked_at' => null, 'so_loi' => 5, 'is_signed' => true,
        ]);

        $this->assertSame(
            CtdtTrangThaiGui::CHUA_KIEM,
            CtdtTrangThaiGui::cua($hoSo),
            'Ho so checked_at = null phai la CHUA_KIEM du so_loi > 0 - so_loi luc nay la '
            . 'du lieu CU, khong dang tin'
        );
    }

    /** @test */
    public function da_kiem_va_sach_thi_khong_bi_chan_nham()
    {
        // Mat kia cua bat bien: cua chan chi duoc dong voi ho so CHUA kiem. Neu no dong
        // voi ca ho so da kiem va sach thi khong ho so nao gui duoc nua.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
        ]);

        $this->assertNotSame(
            CtdtTrangThaiGui::CHUA_KIEM,
            CtdtTrangThaiGui::cua($hoSo),
            'Ho so da kiem (checked_at co gia tri) va sach khong duoc bao la CHUA_KIEM'
        );
        $this->assertSame(CtdtTrangThaiGui::CHUA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function con_loi_chan_duoc_bao_truoc_moi_thu()
    {
        // Ho so con loi thi du co ky cung khong gui duoc - bao ly do gan nhat truoc.
        $hoSo = $this->hoSo(['checked_at' => '2026-08-20 08:00:00', 'so_loi' => 3, 'is_signed' => false]);

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
        // Khang dinh BAT BIEN chu khong khang dinh con so: lay thang cac hang so cua lop
        // thay vi go tay mot danh sach va mot con dem. Them mot trang thai moi ma quen
        // nhan thi test nay do ngay, khong can ai nho sua con so o day.
        $phanChieu = new \ReflectionClass(CtdtTrangThaiGui::class);
        $ma = $phanChieu->getConstants();
        unset($ma['NHAN']);

        $this->assertNotEmpty($ma);
        $this->assertCount(count($ma), array_unique($ma), 'Cac ma trang thai phai khac nhau');
        $this->assertCount(
            count($ma),
            CtdtTrangThaiGui::NHAN,
            'Bang NHAN phai co dung mot dong cho moi trang thai, khong thua khong thieu'
        );

        foreach ($ma as $m) {
            // nhan() lui ve chinh ma khi thieu, nen assertNotEmpty mot minh khong bat duoc
            // thieu nhan - phai soi thang bang NHAN.
            $this->assertArrayHasKey($m, CtdtTrangThaiGui::NHAN, 'Thieu nhan cho ' . $m);
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
