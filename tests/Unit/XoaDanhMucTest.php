<?php

namespace Tests\Unit;

use App\Services\Category\XoaDanhMuc;
use Tests\TestCase;

class XoaDanhMucTest extends TestCase
{
    protected function so()
    {
        return config('danh_muc_bhyt');
    }

    /** @test */
    public function loai_dung_chung_thi_dieu_kien_rong()
    {
        $ra = XoaDanhMuc::dieuKien('icd10', '', $this->so());

        $this->assertSame('icd10_categories', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function loai_dung_chung_thi_bo_qua_ma_co_so_truyen_vao()
    {
        // Tham so lac khong duoc bien thanh dieu kien loc: cot ma_cskcb khong ton tai o
        // bang nay, loc theo no se lam vo truy van.
        $ra = XoaDanhMuc::dieuKien('icd10', '01929', $this->so());

        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function theo_co_so_nhung_khong_chon_co_so_thi_xoa_tat_ca()
    {
        $ra = XoaDanhMuc::dieuKien('medicine', '', $this->so());

        $this->assertSame('medicine_catalogs', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function theo_co_so_va_chon_co_so_thi_loc_dung_co_so_do()
    {
        $ra = XoaDanhMuc::dieuKien('medicine', '01929', $this->so());

        $this->assertSame(['ma_cskcb' => '01929'], $ra['dieu_kien']);
    }

    /**
     * Bay da gap: medical_organizations CUNG co cot ma_cskcb, nhung do la KHOA CUA CHINH
     * DANH MUC (ma cua tung co so trong danh sach), khong phai cot phan tach theo co so.
     * Neu ai do suy theo_co_so tu su ton tai cua cot, test nay se do.
     */
    /** @test */
    public function medical_organization_khong_phai_danh_muc_theo_co_so()
    {
        $ra = XoaDanhMuc::dieuKien('medical_organization', '01929', $this->so());

        $this->assertSame('medical_organizations', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function loai_khong_ton_tai_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        XoaDanhMuc::dieuKien('khong_co_loai_nay', '', $this->so());
    }
}
