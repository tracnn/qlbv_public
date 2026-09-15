<?php

namespace Tests\Unit\Xml3176;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Tests\TestCase;

/**
 * Spec muc 4.3, 6 va phan "Dieu chinh so voi spec" trong plan:
 * - xuat toan bo, KHONG loc theo ngay;
 * - loc co so theo quy uoc "dong khong gan co so la dung chung" (scopeCuaCoSo);
 * - moi o la chuoi de khong mat so 0 dung dau (01929, so dinh danh, ma BHXH).
 */
class DmSheetExportTest extends TestCase
{
    private function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    private function sql($export)
    {
        return str_replace(['`', '"'], '', $export->query()->toSql());
    }

    /** @test */
    public function ten_sheet()
    {
        $this->assertSame('DM khoa-giường', (new DmKhoaGiuongSheetExport(null))->title());
        $this->assertSame('DM NVYT', (new DmNvytSheetExport(null))->title());
    }

    /** @test */
    public function ghi_moi_o_duoi_dang_chuoi()
    {
        $this->assertInstanceOf(WithCustomValueBinder::class, new DmKhoaGiuongSheetExport(null));
        $this->assertInstanceOf(WithCustomValueBinder::class, new DmNvytSheetExport(null));
    }

    /** @test */
    public function cot_khoa_giuong_dung_thu_tu_tieu_de_viet_hoa()
    {
        $e = new DmKhoaGiuongSheetExport(null);

        $this->assertSame(
            ['ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ban_kham', 'giuong_pd',
             'giuong_2015', 'giuong_tk', 'giuong_hstc', 'giuong_hscc', 'ldlk', 'lien_khoa',
             'tu_ngay', 'den_ngay'],
            DmKhoaGiuongSheetExport::COT
        );
        $this->assertSame(array_map('strtoupper', DmKhoaGiuongSheetExport::COT), $e->headings());
    }

    /** @test */
    public function cot_nvyt_du_va_khong_co_cot_ky_thuat()
    {
        $this->assertSame(
            ['ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ma_bhxh', 'ho_ten', 'gioi_tinh',
             'so_dinh_danh', 'chucdanh_nn', 'vi_tri', 'macchn', 'ngaycap_cchn', 'noicap_cchn',
             'phamvi_cm', 'phamvi_cmbs', 'dvkt_khac', 'vb_phancong', 'thoigian_dk',
             'thoigian_ngay', 'thoigian_tuan', 'cskcb_khac', 'cskcb_cgkt', 'qd_cgkt',
             'tu_ngay', 'den_ngay'],
            DmNvytSheetExport::COT
        );

        foreach (['id', 'created_at', 'updated_at'] as $bo) {
            $this->assertNotContains($bo, DmNvytSheetExport::COT);
            $this->assertNotContains($bo, DmKhoaGiuongSheetExport::COT);
        }
    }

    /** @test */
    public function khong_loc_theo_ngay()
    {
        foreach ([new DmKhoaGiuongSheetExport('01929', $this->danhSach()),
                  new DmNvytSheetExport('01929', $this->danhSach())] as $e) {
            $sql = $this->sql($e);
            $this->assertNotContains('between', $sql);
            $this->assertNotContains('created_at', $sql);
            $this->assertNotContains('tu_ngay <', $sql);
            $this->assertNotContains('den_ngay >', $sql);
        }
    }

    /** @test */
    public function ma_co_so_hop_le_thi_loc_va_giu_dong_dung_chung()
    {
        foreach ([new DmKhoaGiuongSheetExport('01929', $this->danhSach()),
                  new DmNvytSheetExport('01929', $this->danhSach())] as $e) {
            $q = $e->query();
            $sql = $this->sql($e);

            $this->assertContains('01929', $q->getBindings());
            $this->assertContains('ma_cskcb is null', $sql, 'Phai giu dong khong gan co so (dung chung)');
        }
    }

    /** @test */
    public function ma_co_so_rong_hoac_khong_hop_le_thi_khong_loc()
    {
        foreach ([null, '', '99999'] as $ma) {
            foreach ([new DmKhoaGiuongSheetExport($ma, $this->danhSach()),
                      new DmNvytSheetExport($ma, $this->danhSach())] as $e) {
                $this->assertNotContains('ma_cskcb =', $this->sql($e), 'ma ' . var_export($ma, true) . ' khong duoc loc');
                $this->assertNotContains('99999', $e->query()->getBindings());
            }
        }
    }

    /** @test */
    public function map_theo_dung_thu_tu_cot()
    {
        $e = new DmNvytSheetExport(null);

        $dong = (object) array_combine(DmNvytSheetExport::COT, DmNvytSheetExport::COT);

        $this->assertSame(DmNvytSheetExport::COT, $e->map($dong));
    }
}
