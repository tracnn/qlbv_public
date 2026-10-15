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
        // Moi lan tra danh muc DVKT/thuoc/VTYT di qua DanhMucTraCuu (co bo dem). Dem CUNG so
        // lan: them mot cho tra moi ma quen loc co so thi test do, thay vi lot im lang.
        $ma = $this->maKhongComment(app_path('Services/Xml3176/DanhMucTraCuu.php'));
        $mau = '(?:ServiceCatalog|MedicineCatalog|MedicalSupplyCatalog)';
        $this->assertSame(6, substr_count($ma, 'cuaCoSo('), 'DanhMucTraCuu phai co dung 6 cho loc theo co so');
        // Moi truy van tren model danh muc deu phai bat dau bang ::cuaCoSo( (khong ::where/::query tran).
        $this->assertSame(6, preg_match_all('/' . $mau . '::cuaCoSo\(/', $ma));
        $this->assertSame(6, preg_match_all('/' . $mau . '::/', $ma));

        // Checker khong duoc tra thang model danh muc (tranh vong qua cho da loc co so).
        $canGoi = [
            'Xml3176Xml2Checker' => 5,   // khop + ham luong duyet + 3 lan ton tai (ma, +ham luong, +SDK)
            'Xml3176Xml3Checker' => 4,   // vtyt + 2 lan dvkt ton tai (nguyen van, ma goc) + hieu luc
            'Xml3176Xml4Checker' => 1,
        ];

        foreach ($canGoi as $tep => $so) {
            $m = $this->maKhongComment(app_path('Services/' . $tep . '.php'));

            $this->assertSame(0, preg_match_all('/' . $mau . '::/', $m),
                "$tep khong duoc tra thang model danh muc");
            $this->assertSame($so, substr_count($m, 'DanhMucTraCuu::'),
                "$tep phai co dung $so cho tra danh muc qua DanhMucTraCuu");
        }
    }
}
