<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class SoDangKyDanhMucTest extends TestCase
{
    protected function so()
    {
        return config('danh_muc_bhyt');
    }

    /** @test */
    public function du_11_bo_va_trung_khoa_voi_cau_hinh_nhap_khau()
    {
        $so = $this->so();

        $this->assertCount(11, $so);

        $khoaSo = array_keys($so);
        $khoaNhap = array_keys(config('catalog_import_mapping'));

        sort($khoaSo);
        sort($khoaNhap);

        $this->assertSame($khoaNhap, $khoaSo,
            'So dang ky phai trung khoa voi catalog_import_mapping');
    }

    /** @test */
    public function moi_bang_deu_ton_tai_va_khop_model()
    {
        foreach ($this->so() as $loai => $x) {
            $this->assertArrayHasKey('ten', $x, "Loai $loai thieu 'ten'");
            $this->assertArrayHasKey('bang', $x, "Loai $loai thieu 'bang'");
            $this->assertArrayHasKey('model', $x, "Loai $loai thieu 'model'");

            $this->assertNotEmpty(
                DB::select(DB::raw("SHOW TABLES LIKE '{$x['bang']}'")),
                "Bang {$x['bang']} cua loai $loai khong ton tai"
            );

            $this->assertTrue(class_exists($x['model']), "Model {$x['model']} khong ton tai");

            $m = new $x['model'];

            $this->assertSame($x['bang'], $m->getTable(),
                "Model {$x['model']} tro toi bang khac voi khai bao");
        }
    }

    /**
     * Chot cung ba loai, KHONG suy ra tu cot ma_cskcb.
     *
     * medical_organizations CUNG co cot ma_cskcb nhung do la KHOA CUA CHINH DANH MUC
     * (ma cua tung co so trong danh sach), khong phai cot phan tach theo co so. Suy ra
     * tu su ton tai cua cot se danh dau nham no.
     */
    /** @test */
    public function chi_dung_ba_loai_theo_co_so()
    {
        $co = [];

        foreach ($this->so() as $loai => $x) {
            $this->assertArrayHasKey('theo_co_so', $x, "Loai $loai thieu 'theo_co_so'");
            $this->assertInternalType('bool', $x['theo_co_so'], "Loai $loai: theo_co_so phai la bool");

            if ($x['theo_co_so']) {
                $co[] = $loai;
            }
        }

        sort($co);

        $this->assertSame(['medical_supply', 'medicine', 'service'], $co);
    }

    /**
     * CatalogImportService::DANH_MUC_THEO_CO_SO la NGUON THU HAI cho cung thong tin ma
     * theo_co_so cua so nay dai dien. Hai ben dang khop nhau, nhung khong co gi bat loi
     * neu sau nay lech: chot bang test de neu BHXH tach them mot danh muc theo co so,
     * chenh lech giua nhap khau va xoa se bi bat ngay o day.
     */
    /** @test */
    public function catalog_import_service_khop_voi_co_theo_co_so_trong_so_dang_ky()
    {
        $tuSo = [];

        foreach ($this->so() as $loai => $x) {
            if (!empty($x['theo_co_so'])) {
                $tuSo[] = $loai;
            }
        }

        $tuImport = \App\Services\CatalogImportService::DANH_MUC_THEO_CO_SO;

        sort($tuSo);
        sort($tuImport);

        $this->assertSame($tuSo, $tuImport,
            'CatalogImportService::DANH_MUC_THEO_CO_SO da lech voi cau hinh theo_co_so trong config/danh_muc_bhyt.php');
    }
}
