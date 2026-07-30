<?php

namespace Tests\Unit\Import;

use DB;
use Tests\TestCase;
use App\Services\CatalogImportService;

/**
 * Bon danh muc cuoi cung chuyen sang ghi theo lo, va duong "gom" bien mat.
 *
 * Do tren cung tep 10.539 dong: duong gom mat 337,9 giay va 21.079 truy van, duong theo lo
 * mat 52,6 giay va ~44 truy van. Bon danh muc con lai deu nho nen chua chet, nhung giu hai
 * duong song song la giu hai bo hanh vi phai sua song song mai mai.
 *
 * medical_staff la cai duy nhat co ngu nghia rieng that: khoa duy nhat linh dong (ma_bhxh,
 * hoac so_dinh_danh khi mau moi khong co MA_BHXH) va NGAYCAP_CCHN phai quy ve chuoi Ymd.
 */
class NhapDanhMucConLaiTheoLoTest extends TestCase
{
    /** @test */
    public function moi_danh_muc_trong_cau_hinh_deu_ghi_theo_lo()
    {
        // Con mot loai ngoai danh sach la con duong gom, tuc con hai bo hanh vi.
        $ngoai = array_diff(array_keys(config('catalog_import_mapping')), CatalogImportService::GHI_THEO_LO);

        $this->assertSame([], array_values($ngoai),
            'Con danh muc di duong gom: ' . implode(', ', $ngoai));
    }

    /** @test */
    public function khong_con_duong_gom_trong_ma_nguon()
    {
        $svc = app(CatalogImportService::class);

        foreach (['importMedicine', 'importMedicalSupply', 'importService', 'importMedicalStaff',
                  'importDepartmentBed', 'importEquipment', 'importJobCategories'] as $ham) {
            $this->assertFalse(method_exists($svc, $ham), "Van con duong nhap tung dong: $ham");
        }
    }

    /** @test */
    public function ten_bang_cua_bon_danh_muc_con_lai()
    {
        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);

        $mong = [
            'medical_staff' => 'medical_staffs',
            'department_bed' => 'department_bed_catalogs',
            'equipment' => 'equipment_catalogs',
            'job_categories' => 'job_categories',
        ];

        foreach ($mong as $loai => $bang) {
            $this->assertSame($bang, $ham->invoke($svc, $loai));
            $this->assertNotEmpty(DB::select('SHOW COLUMNS FROM ' . $bang), "Bang $bang khong ton tai");
        }
    }

    /** @test */
    public function moi_danh_muc_deu_co_ten_bang()
    {
        // GhiTheoLo dung DB::table($bang): thieu mot dong trong bangCua la loi ngay khi nhan
        // dien, truoc khi ghi duoc dong nao.
        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);

        foreach (CatalogImportService::GHI_THEO_LO as $loai) {
            $this->assertNotEmpty($ham->invoke($svc, $loai), "Danh muc $loai chua co ten bang");
        }
    }

    /** @test */
    public function khoa_duy_nhat_cua_nhan_vien_y_te_theo_cot_co_that_trong_tep()
    {
        // Mau moi cua BHXH khong con cot MA_BHXH: bam theo ma_bhxh se coi moi dong la mot ban
        // ghi moi (khoa rong) va chen trung lap moi lan nhap.
        $cfg = config('catalog_import_mapping.medical_staff');

        $this->assertSame(['ma_bhxh'],
            CatalogImportService::khoaDungCho($cfg, ['ma_bhxh' => 3, 'so_dinh_danh' => 6]));
        $this->assertSame(['so_dinh_danh'],
            CatalogImportService::khoaDungCho($cfg, ['so_dinh_danh' => 6]));
    }

    /** @test */
    public function danh_muc_khong_co_khoa_thay_the_thi_giu_khoa_chinh()
    {
        $cfg = config('catalog_import_mapping.equipment');

        $this->assertSame(['ma_may'], CatalogImportService::khoaDungCho($cfg, ['ten_tb' => 1]));
    }

    /** @test */
    public function ngay_cap_cchn_quy_ve_chuoi_ymd()
    {
        // Excel tra ve SO SERIAL cho o dinh dang ngay; cot ngaycap_cchn la varchar va XML
        // giam dinh doi dang YYYYMMDD.
        // Serial 45000 = 15/03/2023 (goc 30/12/1899 cua Excel).
        $tuSerial = CatalogImportService::chuanHoaNgayCchn(['ngaycap_cchn' => 45000]);
        $this->assertSame('20230315', $tuSerial['ngaycap_cchn']);

        $tuChuoi = CatalogImportService::chuanHoaNgayCchn(['ngaycap_cchn' => '4/15/2023 00:00']);
        $this->assertSame('20230415', $tuChuoi['ngaycap_cchn']);
    }

    /** @test */
    public function ngay_cap_cchn_da_dung_dang_thi_giu_nguyen()
    {
        $ra = CatalogImportService::chuanHoaNgayCchn(['ngaycap_cchn' => '20230415']);

        $this->assertSame('20230415', $ra['ngaycap_cchn']);
    }

    /** @test */
    public function ngay_cap_cchn_khong_doc_duoc_thi_giu_nguyen_de_bao_loi_dong()
    {
        // Nem ra day se lam CHET ca lan nhap vi mot o hong; giu nguyen thi chi dong do loi.
        $ra = CatalogImportService::chuanHoaNgayCchn(['ngaycap_cchn' => 'khong phai ngay']);

        $this->assertSame('khong phai ngay', $ra['ngaycap_cchn']);
    }

    /** @test */
    public function o_ngay_cap_cchn_de_trong_khong_bi_bien_thanh_hom_nay()
    {
        // Carbon::parse('') tra ve HOM NAY - ghi lang le mot ngay cap chung chi sai.
        $ra = CatalogImportService::chuanHoaNgayCchn(['ngaycap_cchn' => '']);

        $this->assertSame('', $ra['ngaycap_cchn']);
    }

    /** @test */
    public function danh_muc_khong_co_cot_ngay_cchn_thi_khong_tu_sinh()
    {
        $ra = CatalogImportService::chuanHoaNgayCchn(['ma_may' => 'M01']);

        $this->assertArrayNotHasKey('ngaycap_cchn', $ra);
    }

    /** @test */
    public function co_so_kcb_khong_con_bat_buoc_tuyen_cmkt_va_hang_benh_vien()
    {
        $bb = config('catalog_import_mapping.medical_organization.required_fields');

        $this->assertNotContains('tuyen_cmkt', $bb);
        $this->assertNotContains('hang_benh_vien', $bb);
        $this->assertContains('ma_cskcb', $bb, 'Van phai bat buoc ma co so');
    }

    /** @test */
    public function bang_co_so_kcb_da_co_cot_tuyen_cmkt_va_hang_benh_vien()
    {
        // Truoc day anh xa khai hai truong nay ma bang khong co cot: gia tri nguoi dung nhap
        // bi bo im lang.
        $cot = array_map(function ($c) { return $c->Field; }, DB::select('SHOW COLUMNS FROM medical_organizations'));

        $this->assertContains('tuyen_cmkt', $cot);
        $this->assertContains('hang_benh_vien', $cot);
    }
}
