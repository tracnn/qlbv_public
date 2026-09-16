<?php

namespace Tests\Unit\Import;

use App\Services\CatalogImportService;
use App\Services\ExcelColumnMapper;
use Tests\TestCase;

/**
 * Danh muc benh Phu luc I Thong tu 01/2025/TT-BYT - dang ky theo khuon dvkt_can_ma_may.
 */
class DanhMucBenhPl1NhapTest extends TestCase
{
    const TIEU_DE = ['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'];

    /** @test */
    public function nhan_dien_dung_loai_tu_tieu_de_tep_mau()
    {
        $loai = app(ExcelColumnMapper::class)
            ->detectCatalogType(self::TIEU_DE, config('catalog_import_mapping'));

        $this->assertSame('benh_pl1_cap_chuyen_sau', $loai);
    }

    /** @test */
    public function khong_lam_nhan_nham_danh_muc_icd10_va_dvkt()
    {
        // MA_ICD rat gan MA_ICD10; ExcelColumnMapper co so khop mo.
        $m = app(ExcelColumnMapper::class);
        $cfg = config('catalog_import_mapping');

        $this->assertSame('icd10', $m->detectCatalogType(['MA_ICD10', 'TEN_ICD10', 'MA_CHUONG'], $cfg));
        $this->assertSame('dvkt_can_ma_may', $m->detectCatalogType(['MA_DVKT', 'TEN_DVKT_TT23'], $cfg));
    }

    /** @test */
    public function anh_xa_cot_va_khoa_duy_nhat()
    {
        $c = config('catalog_import_mapping.benh_pl1_cap_chuyen_sau');

        $this->assertSame(['stt', 'ma_icd', 'loai'], $c['required_fields']);
        $this->assertSame(['stt', 'ma_icd', 'loai'], $c['unique_keys']);
        $this->assertSame(['stt', 'ten_benh', 'ma_icd', 'loai', 'tuoi_duoi', 'dieu_kien'], array_keys($c['mapping']));
    }

    /** @test */
    public function ghi_theo_lo_va_lam_moi_tron_bo()
    {
        $this->assertContains('benh_pl1_cap_chuyen_sau', CatalogImportService::GHI_THEO_LO);
        $this->assertContains('benh_pl1_cap_chuyen_sau', CatalogImportService::LAM_MOI_TRON_BO);

        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);
        $this->assertSame('benh_pl1_cap_chuyen_sau', $ham->invoke($svc, 'benh_pl1_cap_chuyen_sau'));
    }

    /** @test */
    public function chuan_hoa_loai_va_ma_icd()
    {
        $ra = CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => ' a17.0† ', 'loai' => 'BAO_GOM']);
        $this->assertSame('bao_gom', $ra['loai']);
        $this->assertSame('A17.0', $ra['ma_icd']);

        $ra = CatalogImportService::chuanHoaBenhPl1(['stt' => 16, 'ma_icd' => 'C38.4', 'loai' => ' Tru ']);
        $this->assertSame('tru', $ra['loai']);
    }

    /** @test */
    public function loai_khong_hop_le_thi_bo_dong()
    {
        $this->assertNull(CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => 'A17.0', 'loai' => 'CO']));
        $this->assertNull(CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => 'A17.0', 'loai' => '']));
    }

    /** @test */
    public function xem_duoc_o_danh_muc_tra_cuu()
    {
        $m = config('danh_muc_tra_cuu.benh_pl1_cap_chuyen_sau');

        $this->assertSame(\App\Models\BHYT\BenhPl1CapChuyenSau::class, $m['model']);
        $this->assertSame(['is_active'], $m['cot_co_khong']);
        $this->assertSame(['stt', 'asc'], $m['sap_xep']);
    }
}
