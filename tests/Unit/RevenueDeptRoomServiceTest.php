<?php
// tests/Unit/RevenueDeptRoomServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RevenueDeptRoomService;

class RevenueDeptRoomServiceTest extends TestCase
{
    /** @test */
    public function build_dept_summary_computes_kpi_and_percentages_natural_order()
    {
        // Mỗi $row = 1 khoa (đã GROUP BY): department_id, department_name, thanh_tien, so_luong
        $rows = [
            (object)['department_id' => 10, 'department_name' => 'Khoa Dược CS1', 'thanh_tien' => 600, 'so_luong' => 100],
            (object)['department_id' => 20, 'department_name' => 'Khoa CĐHA CS1', 'thanh_tien' => 300, 'so_luong' => 50],
            (object)['department_id' => 30, 'department_name' => 'Khoa XN CS1',   'thanh_tien' => 100, 'so_luong' => 20],
        ];

        $res = RevenueDeptRoomService::buildDeptSummary($rows, 7);

        // giữ thứ tự tự nhiên
        $this->assertEquals(['Khoa Dược CS1','Khoa CĐHA CS1','Khoa XN CS1'], array_column($res['by_department'], 'department_name'));
        $this->assertEquals([600, 300, 100], array_column($res['by_department'], 'thanh_tien'));
        $this->assertEquals([60.0, 30.0, 10.0], array_column($res['by_department'], 'pct'));

        $this->assertEquals(1000, $res['kpi']['tong_doanh_thu']);
        $this->assertEquals(3, $res['kpi']['so_khoa']);
        $this->assertEquals(7, $res['kpi']['so_phong']);
    }

    /** @test */
    public function build_dept_summary_handles_empty()
    {
        $res = RevenueDeptRoomService::buildDeptSummary([], 0);
        $this->assertEquals([], $res['by_department']);
        $this->assertEquals(0, $res['kpi']['tong_doanh_thu']);
        $this->assertEquals(0, $res['kpi']['so_khoa']);
        $this->assertEquals(0, $res['kpi']['so_phong']);
    }
}
