<?php

namespace Tests\Unit\Import;

use DB;
use Tests\TestCase;
use App\Services\CatalogImportService;

/**
 * Hai danh mục dùng chung toàn quốc cũng phải ghi theo lô.
 *
 * Do trong CSDL: medical_organizations 13.348 dong, administrative_units 10.542 dong - cung
 * co voi nguong "10.000 dong lam dinh bo nho 208 MB" da do duoc truoc day, va van di duong
 * "gom" roi updateOrCreate tung dong (~27.000 truy van cho medical_organization).
 *
 * Hai danh muc nay co ngu nghia LAM MOI TRON BO: tat is_active cua toan bo ban ghi cu roi
 * bat lai cho dong co trong tep, tuc dong khong con trong tep se nam lai o trang thai tat.
 * Chuyen sang ghi theo lo phai giu nguyen ngu nghia do.
 */
class NhapDanhMucLonTheoLoTest extends TestCase
{
    /** @test */
    public function hai_danh_muc_dung_chung_toan_quoc_di_duong_ghi_theo_lo()
    {
        $this->assertContains('medical_organization', CatalogImportService::GHI_THEO_LO,
            'medical_organizations co 13.348 dong ma con di duong gom');
        $this->assertContains('administrative_unit', CatalogImportService::GHI_THEO_LO,
            'administrative_units co 10.542 dong ma con di duong gom');
    }

    /** @test */
    public function ten_bang_cua_hai_danh_muc_dung_ten_that_trong_csdl()
    {
        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);

        $this->assertSame('administrative_units', $ham->invoke($svc, 'administrative_unit'));
        $this->assertSame('medical_organizations', $ham->invoke($svc, 'medical_organization'));
    }

    /** @test */
    public function chi_hai_danh_muc_nay_lam_moi_tron_bo()
    {
        // Danh muc khac chi cap nhat THEM: dua nham vao day se tat is_active cua du lieu cu
        // ma khong bat lai.
        $this->assertSame(['administrative_unit', 'medical_organization'],
            CatalogImportService::LAM_MOI_TRON_BO);
    }

    /** @test */
    public function dong_co_trong_tep_duoc_bat_lai_dang_dung()
    {
        $ra = CatalogImportService::ganDangDung(['commune_code' => '00001'], 'administrative_unit');
        $this->assertSame(1, $ra['is_active']);

        $ra = CatalogImportService::ganDangDung(['ma_cskcb' => '01929'], 'medical_organization');
        $this->assertSame(1, $ra['is_active']);
    }

    /** @test */
    public function danh_muc_khac_khong_bi_gan_dang_dung()
    {
        // icd10_categories co cot is_active nhung KHONG theo ngu nghia lam moi tron bo: nhap
        // mot tep ICD le se khong duoc phep tat cac ma con lai.
        $ra = CatalogImportService::ganDangDung(['icd_code' => 'A00'], 'icd10');

        $this->assertArrayNotHasKey('is_active', $ra);
    }

    /** @test */
    public function truong_khong_phai_cot_that_bi_loai_truoc_khi_ghi()
    {
        // Duong cu ghi qua Eloquent nen fillable am tham loc bo truong la. Ghi theo lo dung
        // DB::table() - de nguyen truong la la loi "Unknown column" cho CA lo.
        $ra = CatalogImportService::giuCotCoThat(
            ['ma_cskcb' => '01929', 'ten_cskcb' => 'A', 'tuyen_cmkt' => '3'],
            ['ma_cskcb', 'ten_cskcb', 'is_active']
        );

        $this->assertSame(['ma_cskcb' => '01929', 'ten_cskcb' => 'A'], $ra);
    }

    /**
     * @test
     *
     * Canh gac: truong nao trong anh xa cot ma KHONG phai cot that thi gia tri nguoi dung
     * nhap se bi bo IM LANG. Danh sach ngoai le duoi day la no dang ton - xoa bot khi da them
     * cot, va test nay se do neu ai them anh xa moi tro vao cot khong ton tai.
     */
    public function chi_con_dung_nhung_truong_bi_bo_da_biet()
    {
        $ngoaiLe = [
            // required_fields cua medical_organization BAT BUOC hai truong nay, tuc y dinh la
            // phai luu, nhung bang chua co cot: nguoi dung nhap "Tuyen CMKT" va "Hang benh
            // vien" roi tuong da luu. Can migration them cot moi lay lai duoc.
            'medical_organization' => ['tuyen_cmkt', 'hang_benh_vien'],
        ];

        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);
        $cfg = config('catalog_import_mapping');

        foreach (CatalogImportService::GHI_THEO_LO as $loai) {
            $bang = $ham->invoke($svc, $loai);
            $cot = array_map(function ($c) { return $c->Field; }, DB::select('SHOW COLUMNS FROM ' . $bang));
            $thieu = array_values(array_diff(array_keys($cfg[$loai]['mapping']), $cot));

            $this->assertSame(isset($ngoaiLe[$loai]) ? $ngoaiLe[$loai] : [], $thieu,
                "Danh muc $loai: anh xa cot lech voi bang $bang");
        }
    }
}
