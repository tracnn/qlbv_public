<?php

namespace Tests\Unit;

use DB;
use App\Exports\CatalogTemplateExport;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Services\CatalogImportService;
use Tests\TestCase;

/**
 * Danh muc Khoa Phong Giuong: TU_NGAY va MA_CSKCB.
 *
 * Moi mat xich thieu deu hong IM LANG - khong noi nao bao loi, chi la mat du lieu hoac ghi
 * de nham co so. Nen phai chot tung mat xich.
 */
class KhoaPhongGiuongTheoCoSoTest extends TestCase
{
    protected function cotCua($bang)
    {
        $ra = [];

        foreach (DB::select('SHOW COLUMNS FROM ' . $bang) as $c) {
            $ra[] = $c->Field;
        }

        return $ra;
    }

    /** @test */
    public function bang_co_cot_tu_ngay_va_ma_cskcb()
    {
        $cot = $this->cotCua('department_bed_catalogs');

        $this->assertContains('tu_ngay', $cot);
        $this->assertContains('ma_cskcb', $cot);
    }

    /**
     * Diem rui ro nhat: khoa cu chi gom ma_khoa, nen 01929 va 37470 cung ma khoa K24 se de
     * len nhau. Them cot ma_cskcb KHONG tu giai quyet viec nay.
     */
    /** @test */
    public function khoa_duy_nhat_gom_ca_ma_cskcb()
    {
        $cotTrongKhoa = [];

        foreach (DB::select('SHOW INDEX FROM department_bed_catalogs') as $i) {
            if ((int) $i->Non_unique === 0 && $i->Key_name !== 'PRIMARY') {
                $cotTrongKhoa[$i->Key_name][] = $i->Column_name;
            }
        }

        $coKhoaDung = false;

        foreach ($cotTrongKhoa as $cot) {
            if (in_array('ma_khoa', $cot, true) && in_array('ma_cskcb', $cot, true)) {
                $coKhoaDung = true;
            }
        }

        $this->assertTrue($coKhoaDung,
            'Khoa duy nhat phai gom ca ma_khoa va ma_cskcb, neu khong hai co so se de len nhau');
    }

    /** @test */
    public function mapping_co_tu_ngay_va_nhan_du_bien_the_ten()
    {
        $m = config('catalog_import_mapping.department_bed.mapping');

        $this->assertArrayHasKey('tu_ngay', $m);
        $this->assertContains('TU_NGAY', $m['tu_ngay']);
        $this->assertContains('Từ ngày', $m['tu_ngay']);
        $this->assertContains('TU NGAY', $m['tu_ngay']);
    }

    /** @test */
    public function mapping_co_ma_cskcb_va_khoa_duy_nhat()
    {
        $c = config('catalog_import_mapping.department_bed');

        $this->assertArrayHasKey('ma_cskcb', $c['mapping']);
        $this->assertContains('MA_CSKCB', $c['mapping']['ma_cskcb']);
        $this->assertContains('ma_cskcb', $c['unique_keys']);
    }

    /**
     * Thieu trong fillable thi Eloquent AM THAM bo cot khi updateOrCreate().
     */
    /** @test */
    public function model_fillable_du_hai_cot_moi()
    {
        $f = (new DepartmentBedCatalog())->getFillable();

        $this->assertContains('tu_ngay', $f);
        $this->assertContains('ma_cskcb', $f);
    }

    /** @test */
    public function model_co_scope_loc_theo_co_so()
    {
        $this->assertTrue(method_exists(DepartmentBedCatalog::class, 'scopeCuaCoSo'));
    }

    /** @test */
    public function danh_muc_nay_nam_trong_danh_sach_theo_co_so()
    {
        $this->assertContains('department_bed', CatalogImportService::DANH_MUC_THEO_CO_SO);
    }

    /**
     * Hai nguon su that cho cung mot khai niem. Lech nhau thi chuc nang xoa theo co so se
     * xoa nham du lieu cua co so khac ma khong bao gi.
     */
    /** @test */
    public function hai_nguon_su_that_ve_danh_muc_theo_co_so_khop_nhau()
    {
        $tuConfig = [];

        foreach (config('danh_muc_bhyt', []) as $loai => $x) {
            if (!empty($x['theo_co_so'])) {
                $tuConfig[] = $loai;
            }
        }

        $tuHangSo = CatalogImportService::DANH_MUC_THEO_CO_SO;

        sort($tuConfig);
        sort($tuHangSo);

        $this->assertSame($tuHangSo, $tuConfig,
            'DANH_MUC_THEO_CO_SO va theo_co_so trong danh_muc_bhyt.php phai khop nhau');
    }

    /** @test */
    public function man_danh_sach_hien_hai_cot_moi()
    {
        $ma = file_get_contents(
            base_path('resources/views/category/bhyt/department_bed_catalog.blade.php')
        );

        $this->assertContains('<th>Từ ngày</th>', $ma);
        $this->assertContains('<th>MA_CSKCB</th>', $ma);
        $this->assertContains('"data": "tu_ngay"', $ma);
        $this->assertContains('"data": "ma_cskcb"', $ma);

        // O trong nghia la dong dung chung moi co so, khong phai thieu du lieu.
        $this->assertContains("'Dùng chung'", $ma);
    }

    /**
     * Lech so <th> va so phan tu "columns" lam DataTables vo ngay khi tai trang, va loi chi
     * hien o console trinh duyet chu khong o phia may chu.
     */
    /** @test */
    public function so_tieu_de_khop_so_cot_o_moi_man_danh_muc()
    {
        foreach (['department_bed', 'medicine', 'service', 'medical_supply'] as $loai) {
            $ma = file_get_contents(
                base_path('resources/views/category/bhyt/' . $loai . '_catalog.blade.php')
            );

            preg_match('~<thead>(.*?)</thead>~s', $ma, $m);
            $soTh = preg_match_all('~<th>~', isset($m[1]) ? $m[1] : '');

            preg_match('~"columns"\s*:\s*\[(.*?)\n\s*\],~s', $ma, $c);
            $soCot = preg_match_all('~\{\s*"data"~', isset($c[1]) ? $c[1] : '');

            $this->assertSame($soTh, $soCot,
                $loai . ': so <th> (' . $soTh . ') khac so cot (' . $soCot . ')');
        }
    }

    /** @test */
    public function bieu_mau_sinh_ra_co_du_hai_cot_moi()
    {
        $x = new CatalogTemplateExport('department_bed');
        $header = $x->headers();

        $this->assertContains('TU_NGAY', $header);
        $this->assertContains('MA_CSKCB', $header);

        // Cot ma co so phai duoc to mau rieng de nguoi dung thay - bo trong thi danh muc
        // thanh "dung chung moi co so" mot cach im lang.
        $this->assertContains('MA_CSKCB', $x->facilityHeaders());
    }
}
