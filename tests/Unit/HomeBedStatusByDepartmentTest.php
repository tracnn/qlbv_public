<?php
// tests/Unit/HomeBedStatusByDepartmentTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeBedStatusByDepartmentTest extends TestCase
{
    /** @test */
    public function it_builds_used_free_utilization_and_total()
    {
        // Mỗi $row = 1 khoa: department_name, tong_giuong, dang_dung
        $rows = [
            (object)['department_name' => 'Khoa Nhi CS1',    'tong_giuong' => 168, 'dang_dung' => 72],
            (object)['department_name' => 'Khoa Nội TH CS1', 'tong_giuong' => 100, 'dang_dung' => 90],
            (object)['department_name' => 'Khoa Quá tải',     'tong_giuong' => 10,  'dang_dung' => 12], // overcrowd: free kẹp 0
        ];

        $res = HomeController::buildBedStatusByDepartmentSeries($rows);

        $this->assertEquals(['Khoa Nhi CS1','Khoa Nội TH CS1','Khoa Quá tải'], $res['categories']);
        $this->assertEquals([72, 90, 12], $res['used']);
        $this->assertEquals([96, 10, 0], $res['free']);          // con_trong = max(0, tong - dang)
        $this->assertEquals([43, 90, 120], $res['utilization']); // round(dang/tong*100); 72/168=42.857->43; 12/10=120

        $this->assertEquals(278, $res['total']['tong']);        // 168+100+10
        $this->assertEquals(174, $res['total']['dang_dung']);   // 72+90+12
        $this->assertEquals(106, $res['total']['con_trong']);   // 96+10+0
        $this->assertEquals(63, $res['total']['cong_suat']);    // round(174/278*100)=62.59->63
    }

    /** @test */
    public function it_handles_empty_input()
    {
        $res = HomeController::buildBedStatusByDepartmentSeries([]);
        $this->assertEquals([], $res['categories']);
        $this->assertEquals([], $res['used']);
        $this->assertEquals([], $res['free']);
        $this->assertEquals(0, $res['total']['tong']);
        $this->assertEquals(0, $res['total']['cong_suat']);
    }
}
