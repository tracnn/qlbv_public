<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanCatalogService;

class GiaoBanCatalogServiceTest extends TestCase
{
    /** @test */
    public function khai_bao_du_9_danh_muc_va_moi_muc_co_bang_cot_id_cot_ten()
    {
        $c = GiaoBanCatalogService::CATALOGS;

        $this->assertCount(9, $c);
        foreach (['service_type', 'diim_type', 'test_type', 'patient_type', 'treatment_type',
                  'end_type', 'service', 'room', 'bed'] as $key) {
            $this->assertArrayHasKey($key, $c, "Thieu danh muc $key");
            $this->assertArrayHasKey('table', $c[$key]);
            $this->assertArrayHasKey('id_col', $c[$key]);
            $this->assertArrayHasKey('name_col', $c[$key]);
        }
    }

    /** @test */
    public function ba_danh_muc_lon_duoc_danh_dau_remote_con_lai_thi_khong()
    {
        $this->assertEquals(['service', 'room', 'bed'],
            array_values(array_diff(GiaoBanCatalogService::allKeys(), GiaoBanCatalogService::smallKeys())));

        $this->assertTrue(GiaoBanCatalogService::isRemote('service'));
        $this->assertFalse(GiaoBanCatalogService::isRemote('diim_type'));
    }

    /** @test */
    public function end_type_dinh_danh_bang_code_khong_phai_id()
    {
        // metric end_type luu ["RV","CV"] chu khong luu id -> phai danh dau rieng
        $this->assertEquals('treatment_end_type_code', GiaoBanCatalogService::CATALOGS['end_type']['id_col']);
    }

    /** @test */
    public function room_dung_view_v_his_room_khong_phai_his_room_hay_his_execute_room()
    {
        // tdl_execute_room_id (khoa duoc GiaoBanMetricService::buildServiceCountSql loc) tro toi
        // his_room.id, nhung his_room khong co cot ten; his_execute_room co ten nhung id lai sai
        // khoa (~53% lech). v_his_room la view dang duoc RevenueDeptRoomService dung dung cho
        // chinh muc dich nay -> phai giu nguyen, khong "sua lai cho dung ten bang".
        $this->assertEquals('v_his_room', GiaoBanCatalogService::CATALOGS['room']['table']);
        $this->assertEquals('room_name', GiaoBanCatalogService::CATALOGS['room']['name_col']);
    }

    /** @test */
    public function dung_sql_danh_muc_nho_lay_dung_bang_va_cot()
    {
        list($sql, $binds) = GiaoBanCatalogService::buildSmallSql('diim_type');

        $this->assertContains('FROM his_diim_type', $sql);
        $this->assertContains('diim_type_name', $sql);
        $this->assertContains('is_delete = 0', $sql);
        $this->assertSame([], $binds);
    }

    /** @test */
    public function danh_muc_end_type_lay_code_lam_dinh_danh()
    {
        list($sql, ) = GiaoBanCatalogService::buildSmallSql('end_type');

        $this->assertContains('treatment_end_type_code AS ma', $sql);
        $this->assertNotContains('id AS ma', $sql);
    }

    /** @test */
    public function danh_muc_khong_ton_tai_thi_nem_loi()
    {
        $this->expectException(\InvalidArgumentException::class);
        GiaoBanCatalogService::buildSmallSql('khong_ton_tai');
    }

    /** @test */
    public function tim_danh_muc_lon_gioi_han_30_dong_va_bo_dau()
    {
        list($sql, $binds) = GiaoBanCatalogService::buildSearchSql('service', 'sieu am');

        $this->assertContains('ROWNUM <= 30', $sql);
        $this->assertContains('FROM his_service', $sql);
        $this->assertArrayHasKey('q1', $binds);
        $this->assertContains('%sieu am%', $binds['q1']);
    }

    /** @test */
    public function tra_nguoc_theo_ids_chi_nhan_so_nguyen()
    {
        list($sql, ) = GiaoBanCatalogService::buildByIdsSql('room', ['12', 34, 'x']);

        // 'x' bi ep ve 0, khong duoc chen chuoi vao SQL
        $this->assertContains('IN (12,34,0)', $sql);
        $this->assertNotContains("'x'", $sql);
    }

    /** @test */
    public function tra_nguoc_voi_mang_rong_khong_khop_gi()
    {
        list($sql, ) = GiaoBanCatalogService::buildByIdsSql('room', []);
        $this->assertContains('IN (-1)', $sql);
    }

    /** @test */
    public function danh_muc_nho_khong_dung_duong_tim_kiem_remote()
    {
        $this->expectException(\InvalidArgumentException::class);
        GiaoBanCatalogService::buildSearchSql('diim_type', 'abc');
    }
}
