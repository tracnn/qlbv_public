<?php

namespace Tests\Unit\BHYT;

use App\Models\BHYT\DepartmentBedCatalog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kiem tra logic gom nhom danh sach khoa cho dropdown loc XML3176.
 *
 * An toan CSDL: tro chinh ket noi ten 'mysql' (ket noi mac dinh cua model) sang
 * SQLite bo nho, tu tao bang department_bed_catalogs (bang nay khong co migration),
 * KHONG cham CSDL that. Xem memory qlbv-test-infra-gotchas: cam RefreshDatabase.
 */
class DepartmentBedCatalogDropdownTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config(['database.connections.mysql' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);
        DB::purge('mysql');

        DB::connection('mysql')->getSchemaBuilder()->create('department_bed_catalogs', function ($t) {
            $t->increments('id');
            $t->string('ma_loai_kcb')->nullable();
            $t->string('ma_khoa')->nullable();
            $t->string('ten_khoa')->nullable();
            $t->string('ma_cskcb')->nullable();
        });
    }

    /** @test */
    public function danh_sach_chon_khoa_gom_nhom_trung_ma_khoa_va_sap_tang_dan()
    {
        DepartmentBedCatalog::insert([
            ['ma_loai_kcb' => '01', 'ma_khoa' => 'K002', 'ten_khoa' => 'Ngoai tong hop'],
            ['ma_loai_kcb' => '01', 'ma_khoa' => 'K001', 'ten_khoa' => 'Noi tong hop'],
            // Cung ma_khoa K001, khac ma_loai_kcb -> phai gom thanh 1 dong
            ['ma_loai_kcb' => '03', 'ma_khoa' => 'K001', 'ten_khoa' => 'Noi tong hop'],
        ]);

        $ds = DepartmentBedCatalog::danhSachChonKhoa();

        $this->assertCount(2, $ds);
        // Sap xep tang dan theo ma_khoa
        $this->assertEquals(['K001', 'K002'], $ds->pluck('ma_khoa')->all());
        $this->assertEquals('Noi tong hop', $ds->firstWhere('ma_khoa', 'K001')->ten_khoa);
    }

    /** @test */
    public function danh_sach_chon_khoa_bo_ma_khoa_rong_hoac_null()
    {
        DepartmentBedCatalog::insert([
            ['ma_khoa' => 'K001', 'ten_khoa' => 'Noi'],
            ['ma_khoa' => '',     'ten_khoa' => 'Chuoi rong'],
            ['ma_khoa' => null,   'ten_khoa' => 'Null'],
        ]);

        $ds = DepartmentBedCatalog::danhSachChonKhoa();

        $this->assertCount(1, $ds);
        $this->assertEquals('K001', $ds->first()->ma_khoa);
    }

    /** @test */
    public function danh_sach_chon_khoa_rong_khi_khong_co_du_lieu()
    {
        $this->assertCount(0, DepartmentBedCatalog::danhSachChonKhoa());
    }
}
