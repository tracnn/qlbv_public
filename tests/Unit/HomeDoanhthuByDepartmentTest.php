<?php
// tests/Unit/HomeDoanhthuByDepartmentTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeDoanhthuByDepartmentTest extends TestCase
{
    /** @test */
    public function it_builds_categories_and_data_in_natural_order()
    {
        // Mỗi $row = 1 khoa (đã GROUP BY ở SQL): department_name, thanh_tien
        $rows = [
            (object)['department_name' => 'Khoa Dược CS1',     'thanh_tien' => 2580191563],
            (object)['department_name' => 'Khoa CĐHA CS1',     'thanh_tien' => 1146216800],
            (object)['department_name' => 'Khoa Xét nghiệm CS1','thanh_tien' => 881619700],
        ];

        $res = HomeController::buildDoanhthuByDepartmentSeries($rows);

        // Giữ nguyên thứ tự đầu vào (không sắp xếp)
        $this->assertEquals(['Khoa Dược CS1','Khoa CĐHA CS1','Khoa Xét nghiệm CS1'], $res['categories']);
        $this->assertEquals([2580191563, 1146216800, 881619700], $res['data']);
        $this->assertEquals(4608028063, $res['total']);
    }

    /** @test */
    public function it_handles_empty_input()
    {
        $res = HomeController::buildDoanhthuByDepartmentSeries([]);
        $this->assertEquals([], $res['categories']);
        $this->assertEquals([], $res['data']);
        $this->assertEquals(0, $res['total']);
    }
}
