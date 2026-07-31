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
    public function khong_nhin_thay_khoa_nao_thi_bi_coi_la_chua_phan_cong()
    {
        $this->assertTrue(GiaoBanPermission::chuaPhanCongKhoa(false, []));
        $this->assertFalse(GiaoBanPermission::chuaPhanCongKhoa(false, [2]));
        // admin khong bao gio bi coi la chua phan cong — ho thay tat
        $this->assertFalse(GiaoBanPermission::chuaPhanCongKhoa(true, []));
    }

    /** @test */
    public function duoc_gan_khoa_da_tat_is_active_cung_bi_coi_la_chua_phan_cong()
    {
        // Nhan danh sach NHIN THAY chu khong phai danh sach duoc gan: user gan khoa 9 nhung
        // khoa 9 da tat -> visibleDeptConfigIds tra [] -> man trong -> phai bao cho ho biet.
        $nhinThay = GiaoBanPermission::visibleDeptConfigIds(false, [9], [1, 2, 3]);

        $this->assertSame([], $nhinThay);
        $this->assertTrue(GiaoBanPermission::chuaPhanCongKhoa(false, $nhinThay));
    }

    // ===== Quyen LAY SO LIEU tu HIS =====

    /** @test */
    public function admin_luon_duoc_lay_so_lieu()
    {
        $this->assertTrue(GiaoBanPermission::canFetchData(true, []));
        $this->assertTrue(GiaoBanPermission::canFetchData(true, [3]));
    }

    /** @test */
    public function nguoi_duoc_gan_khoa_duoc_lay_so_lieu()
    {
        $this->assertTrue(GiaoBanPermission::canFetchData(false, [3]));
        $this->assertTrue(GiaoBanPermission::canFetchData(false, [3, 5]));
    }

    /** @test */
    public function chua_duoc_gan_khoa_nao_thi_khong_duoc_lay_so_lieu()
    {
        $this->assertFalse(GiaoBanPermission::canFetchData(false, []));
    }

    // ===== Khung gio hieu luc khi lay so lieu (chot o server, khong de client de len) =====

    /** @test */
    public function admin_luon_dung_khung_gio_gui_len()
    {
        // Admin co toan quyen doi khung gio, ke ca khi bao cao da tung fetch voi khung khac.
        $this->assertSame(
            ['2026-07-30 08:00:00', '2026-07-31 08:00:00'],
            GiaoBanPermission::khungGioHieuLuc(
                true, true,
                '2026-07-30 08:00:00', '2026-07-31 08:00:00',
                '2026-07-30 07:00:00', '2026-07-31 07:00:00'
            )
        );
    }

    /** @test */
    public function nguoi_khoa_bao_cao_da_fetch_thi_dung_khung_gio_da_luu_bo_qua_gui_len()
    {
        // Mot cu bam cua nguoi khoa khong duoc phep revert khung gio KHTH da dat.
        $this->assertSame(
            ['2026-07-30 07:00:00', '2026-07-31 07:00:00'],
            GiaoBanPermission::khungGioHieuLuc(
                false, true,
                '2026-07-30 08:00:00', '2026-07-31 08:00:00',
                '2026-07-30 07:00:00', '2026-07-31 07:00:00'
            )
        );
    }

    /** @test */
    public function nguoi_khoa_bao_cao_chua_fetch_thi_dung_khung_gio_gui_len()
    {
        // Lan tao dau tien: chua co gi da luu de bao ve, dung nguyen gia tri gui len.
        $this->assertSame(
            ['2026-07-30 07:00:00', '2026-07-31 07:00:00'],
            GiaoBanPermission::khungGioHieuLuc(
                false, false,
                '2026-07-30 07:00:00', '2026-07-31 07:00:00',
                null, null
            )
        );
    }
}
