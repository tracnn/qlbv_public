<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;

class Xml3176DanhMucCoSoTest extends TestCase
{
    use LocComment;

    /** @test */
    public function ba_model_danh_muc_deu_co_scope_cua_co_so()
    {
        foreach ([
            \App\Models\BHYT\ServiceCatalog::class,
            \App\Models\BHYT\MedicineCatalog::class,
            \App\Models\BHYT\MedicalSupplyCatalog::class,
        ] as $lop) {
            $this->assertTrue(method_exists($lop, 'scopeCuaCoSo'), "$lop thieu scope cuaCoSo");
        }
    }

    /** @test */
    public function scope_khop_dong_rong_va_dong_dung_co_so()
    {
        // Dong rong = dung chung moi co so. Day la dieu kien de trien khai khong lam tat
        // cac kiem tra danh muc dang chay tren may chu that.
        $sql = strtolower(\App\Models\BHYT\ServiceCatalog::cuaCoSo('01929')->toSql());

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('is null', $sql);
    }

    /** @test */
    public function khong_truyen_ma_co_so_thi_scope_khong_them_dieu_kien()
    {
        $khong = \App\Models\BHYT\ServiceCatalog::cuaCoSo(null)->toSql();
        $goc = \App\Models\BHYT\ServiceCatalog::query()->toSql();

        $this->assertSame($goc, $khong);
    }

    /** @test */
    public function tam_cho_tra_danh_muc_cua_xml3176_deu_loc_theo_co_so()
    {
        // Dem CUNG so lan: them mot cho tra danh muc moi ma quen loc co so thi test do,
        // thay vi lot im lang.
        $canCo = [
            'Xml3176Xml2Checker' => 4,
            'Xml3176Xml3Checker' => 3,
            'Xml3176Xml4Checker' => 1,
        ];

        foreach ($canCo as $tep => $so) {
            $ma = $this->maKhongComment(app_path('Services/' . $tep . '.php'));

            $this->assertSame($so, substr_count($ma, 'cuaCoSo('),
                "$tep phai co dung $so cho loc theo co so");
        }
    }
}
