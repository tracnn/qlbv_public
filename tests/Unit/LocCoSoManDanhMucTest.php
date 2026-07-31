<?php

namespace Tests\Unit;

use App\Models\BHYT\DepartmentBedCatalog;
use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Bo loc co so o bon man danh muc theo co so.
 *
 * Ngu nghia: chon mot co so = danh muc CO HIEU LUC cho co so do, gom ca dong dung chung
 * (ma_cskcb rong). Do la chinh tap dong he thong ap dung khi kiem ho so, nen phai dung lai
 * scopeCuaCoSo chu khong viet dieu kien rieng - viet rieng thi danh sach hien ra co the lech
 * voi thu thuc te duoc dung.
 */
class LocCoSoManDanhMucTest extends TestCase
{
    use LocComment;

    public function bonMan()
    {
        return [
            ['medicine_catalog', 'fetchMedicineCatalog', 'indexMedicineCatalog'],
            ['medical_supply_catalog', 'fetchMedicalSupplyCatalog', 'indexMedicalSupplyCatalog'],
            ['service_catalog', 'fetchServiceCatalog', 'indexServiceCatalog'],
            ['department_bed_catalog', 'fetchDepartmentBedCatalog', 'indexDepartmentBedCatalog'],
        ];
    }

    protected function maController()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Http/Controllers/Category/CategoryBHYTController.php')
        );
    }

    /** Than cua mot phuong thuc, tinh tu ten no toi ten phuong thuc ke tiep. */
    protected function thanHam($ma, $ten)
    {
        $dau = strpos($ma, 'function ' . $ten . '(');
        $this->assertNotFalse($dau, 'Khong tim thay ' . $ten);

        $sau = strpos($ma, 'public function ', $dau + 10);

        return $sau === false ? substr($ma, $dau) : substr($ma, $dau, $sau - $dau);
    }

    /** @test */
    public function bon_endpoint_deu_loc_theo_co_so()
    {
        $ma = $this->maController();

        foreach ($this->bonMan() as list($_, $fetch, $__)) {
            $than = $this->thanHam($ma, $fetch);

            $this->assertContains('cuaCoSo(', $than, $fetch . ' khong loc theo co so');
            $this->assertContains('Request $request', $than, $fetch . ' khong nhan Request');
            $this->assertNotContains('::query()', $than,
                $fetch . ' con dung query() - phai dung cuaCoSo() de khop quy tac he thong');
        }
    }

    /** @test */
    public function bon_man_deu_duoc_truyen_danh_sach_co_so()
    {
        $ma = $this->maController();

        foreach ($this->bonMan() as list($_, $__, $index)) {
            $than = $this->thanHam($ma, $index);

            $this->assertContains('danhSachCoSo', $than, $index . ' khong truyen danh sach co so');
        }
    }

    /** @test */
    public function bon_blade_deu_co_o_loc_va_gui_tham_so()
    {
        foreach ($this->bonMan() as list($view, $_, $__)) {
            $ma = file_get_contents(
                base_path('resources/views/category/bhyt/' . $view . '.blade.php')
            );

            $this->assertContains("@include('partials.ma_cskcb'", $ma, $view . ' thieu o loc');
            $this->assertContains('d.ma_cskcb', $ma, $view . ' khong gui ma_cskcb trong ajax');
            $this->assertContains("\$('#ma_cskcb').on('change'", $ma,
                $view . ' khong nap lai khi doi co so');
        }
    }

    /**
     * Danh muc DUNG CHUNG khong duoc co o loc co so: chung khong co cot ma_cskcb, them o loc
     * vao chi gay hieu nham.
     */
    /** @test */
    public function danh_muc_dung_chung_khong_co_o_loc()
    {
        foreach (['icd10_catalog', 'equipment_catalog'] as $view) {
            $tep = base_path('resources/views/category/bhyt/' . $view . '.blade.php');

            if (!file_exists($tep)) {
                continue;
            }

            $this->assertNotContains("@include('partials.ma_cskcb'", file_get_contents($tep),
                $view . ' la danh muc dung chung, khong duoc co o loc co so');
        }
    }

    /**
     * Kiem hanh vi that: ma rong = khong loc.
     */
    /** @test */
    public function ma_rong_thi_khong_loc()
    {
        $tong = DepartmentBedCatalog::count();

        $this->assertSame($tong, DepartmentBedCatalog::cuaCoSo('')->count());
        $this->assertSame($tong, DepartmentBedCatalog::cuaCoSo(null)->count());
    }

    /**
     * Kiem hanh vi that: chon co so ra dong cua co so do CONG dong dung chung.
     */
    /** @test */
    public function chon_co_so_gom_ca_dong_dung_chung()
    {
        $rieng = DepartmentBedCatalog::where('ma_cskcb', '01929')->count();
        $chung = DepartmentBedCatalog::where(function ($w) {
            $w->whereNull('ma_cskcb')->orWhere('ma_cskcb', '');
        })->count();

        $this->assertSame($rieng + $chung, DepartmentBedCatalog::cuaCoSo('01929')->count());
    }
}
