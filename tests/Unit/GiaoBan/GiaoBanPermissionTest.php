<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanPermission;

class GiaoBanPermissionTest extends TestCase
{
    /** @test */
    public function admin_can_edit_any_dept_even_when_not_assigned()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(true, [], 5));
    }

    /** @test */
    public function khoa_user_can_edit_only_assigned_depts()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(false, [3, 5], 5));
        $this->assertFalse(GiaoBanPermission::canEditDept(false, [3, 5], 7));
    }

    /** @test */
    public function nobody_edits_when_report_is_final()
    {
        $this->assertFalse(GiaoBanPermission::canEditReport('final', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', false));
    }

    // ===== Quyen NHIN THAY (tach khoi quyen SUA) =====

    /** @test */
    public function admin_nhin_thay_toan_bo_khoa()
    {
        $this->assertEquals([1, 2, 3], GiaoBanPermission::visibleDeptConfigIds(true, [], [1, 2, 3]));
        $this->assertEquals([1, 2, 3], GiaoBanPermission::visibleDeptConfigIds(true, [2], [1, 2, 3]));
    }

    /** @test */
    public function khoa_user_chi_nhin_thay_khoa_duoc_gan()
    {
        $this->assertEquals([2], GiaoBanPermission::visibleDeptConfigIds(false, [2], [1, 2, 3]));
        $this->assertEquals([1, 3], GiaoBanPermission::visibleDeptConfigIds(false, [3, 1], [1, 2, 3]));
    }

    /** @test */
    public function khoa_duoc_gan_nhung_da_ngung_hoat_dong_thi_khong_hien()
    {
        // config bi tat is_active -> khong nam trong $allActiveIds -> khong duoc tra ve
        $this->assertEquals([2], GiaoBanPermission::visibleDeptConfigIds(false, [2, 9], [1, 2, 3]));
    }

    /** @test */
    public function chua_duoc_gan_khoa_nao_thi_khong_nhin_thay_gi()
    {
        $this->assertEquals([], GiaoBanPermission::visibleDeptConfigIds(false, [], [1, 2, 3]));
    }

    /** @test */
    public function ket_qua_giu_dung_thu_tu_cua_danh_sach_khoa_hoat_dong()
    {
        // $allActiveIds da duoc sap theo sort_order -> ket qua phai bam theo do,
        // khong bam theo thu tu ban ghi gan trong giaoban_user_departments.
        $this->assertEquals([5, 1, 9], GiaoBanPermission::visibleDeptConfigIds(false, [9, 5, 1], [5, 1, 9]));
    }

    /** @test */
    public function so_dang_chuoi_van_khop()
    {
        // pluck() tu DB co the tra ve chuoi tuy driver
        $this->assertEquals([2], GiaoBanPermission::visibleDeptConfigIds(false, ['2'], [1, 2, 3]));
        $this->assertEquals([2], GiaoBanPermission::visibleDeptConfigIds(false, [2], ['1', '2', '3']));
    }

    /** @test */
    public function chua_gan_khoa_nao_thi_bi_coi_la_chua_phan_cong()
    {
        $this->assertTrue(GiaoBanPermission::chuaPhanCongKhoa(false, []));
        $this->assertFalse(GiaoBanPermission::chuaPhanCongKhoa(false, [2]));
        // admin khong bao gio bi coi la chua phan cong — ho thay tat
        $this->assertFalse(GiaoBanPermission::chuaPhanCongKhoa(true, []));
    }
}
