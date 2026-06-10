<?php
// tests/Unit/HomeServiceByMachineTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeServiceByMachineTest extends TestCase
{
    /** @test */
    public function it_builds_by_group_and_by_machine_in_natural_order()
    {
        // Mỗi $row = 1 máy (đã GROUP BY ở SQL): machine_name, machine_group_code, so_luong
        $rows = [
            (object)['machine_name' => 'CT-01',  'machine_group_code' => 'CL',  'so_luong' => 254],
            (object)['machine_name' => 'TNT-04', 'machine_group_code' => 'TNT', 'so_luong' => 18],
            (object)['machine_name' => 'TNT-11', 'machine_group_code' => 'TNT', 'so_luong' => 17],
            (object)['machine_name' => 'SA-01',  'machine_group_code' => 'SA',  'so_luong' => 37],
        ];

        $res = HomeController::buildServiceByMachineSeries($rows);

        // by_machine: giữ nguyên thứ tự đầu vào (không sắp xếp)
        $this->assertEquals(['CT-01','TNT-04','TNT-11','SA-01'], $res['by_machine']['labels']);
        $this->assertEquals([254, 18, 17, 37], $res['by_machine']['data']);
        $this->assertEquals(['CL','TNT','TNT','SA'], $res['by_machine']['groups']);
        $this->assertEquals(326, $res['by_machine']['total']);

        // by_group: gộp theo nhóm (TNT = 18+17 = 35), giữ thứ tự xuất hiện đầu tiên
        $this->assertEquals(['CL','TNT','SA'], $res['by_group']['labels']);
        $this->assertEquals([254, 35, 37], $res['by_group']['data']);
        $this->assertEquals(326, $res['by_group']['total']);
    }

    /** @test */
    public function it_maps_empty_group_to_placeholder_and_handles_empty_input()
    {
        $rows = [ (object)['machine_name' => 'X', 'machine_group_code' => null, 'so_luong' => 5] ];
        $res = HomeController::buildServiceByMachineSeries($rows);
        $this->assertEquals(['(trống)'], $res['by_group']['labels']);
        $this->assertEquals(['(trống)'], $res['by_machine']['groups']);

        $empty = HomeController::buildServiceByMachineSeries([]);
        $this->assertEquals([], $empty['by_group']['labels']);
        $this->assertEquals([], $empty['by_machine']['labels']);
        $this->assertEquals(0, $empty['by_group']['total']);
    }
}
