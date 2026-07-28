<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176Xml3Checker;

/**
 * Phep so ten DVKT voi danh muc phe duyet.
 *
 * Quy tac XML3_INVALID_TEN_DICH_VU tung bi chu thich tat. Ban chu thich so
 * $data->ten_dich_vu voi $validServiceExists->ten_dich_vu trong khi $validServiceExists
 * la Collection - truy thuoc tinh tren Collection ra null nen MOI dong DVKT deu thanh vi
 * pham. Bo test nay khoa ngu nghia dung truoc khi bat lai.
 */
class Xml3TenDichVuTest extends TestCase
{
    private function danhMuc(array $ten)
    {
        return collect(array_map(function ($t) {
            return (object) ['ten_dich_vu' => $t];
        }, $ten));
    }

    /** @test */
    public function gom_ten_phe_duyet_da_trim_va_bo_trung()
    {
        $ra = Xml3176Xml3Checker::tenPheDuyet($this->danhMuc(['  A  ', 'A', 'B', '', null]));

        $this->assertSame(['A', 'B'], $ra);
    }

    /** @test */
    public function ten_khop_thi_khong_lech()
    {
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('A', ['A', 'B']));
    }

    /** @test */
    public function khop_bat_ky_ten_nao_deu_dat()
    {
        // MOT ma DVKT co the co NHIEU dong danh muc (nhieu dot phe duyet, nhieu quy trinh).
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('B', ['A', 'B', 'C']));
    }

    /** @test */
    public function ten_khong_khop_dong_nao_thi_lech()
    {
        $this->assertTrue(Xml3176Xml3Checker::tenLechDanhMuc('X', ['A', 'B']));
    }

    /** @test */
    public function lech_hoa_thuong_van_tinh_la_lech()
    {
        // So TUYET DOI, thong nhat voi INVALID_DRUG_NAME va INVALID_MATERIAL_NAME.
        $this->assertTrue(Xml3176Xml3Checker::tenLechDanhMuc('a', ['A']));
    }

    /** @test */
    public function khoang_trang_dau_duoi_khong_tinh_la_lech()
    {
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('  A  ', ['A']));
    }

    /** @test */
    public function ten_khai_rong_thi_khong_bao_lech()
    {
        // Thieu ten la viec cua quy tac khac, khong chong hai loi len cung mot dong.
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('', ['A']));
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc(null, ['A']));
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('   ', ['A']));
    }

    /** @test */
    public function danh_muc_khong_co_ten_nao_thi_khong_bao_lech()
    {
        // Day la ca gay ra loi cu: Collection tra null -> moi dong thanh vi pham.
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('X', []));
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('X', Xml3176Xml3Checker::tenPheDuyet($this->danhMuc([null, '']))));
    }

    /** @test */
    public function chi_neu_toi_da_ba_ten_trong_mo_ta()
    {
        $mo = Xml3176Xml3Checker::neuTenPheDuyet(['T1', 'T2', 'T3', 'T4']);

        $this->assertContains('T1', $mo);
        $this->assertContains('T3', $mo);
        $this->assertNotContains('T4', $mo);
        $this->assertContains('…', $mo);
    }

    /** @test */
    public function it_hon_ba_ten_thi_khong_them_dau_ba_cham()
    {
        $mo = Xml3176Xml3Checker::neuTenPheDuyet(['T1', 'T2']);

        $this->assertNotContains('…', $mo);
    }
}
