<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanDutyService;

class GiaoBanDutyServiceTest extends TestCase
{
    /** @test */
    public function copy_rows_keeps_fields_and_drops_ids()
    {
        $prev = [
            (object) ['id' => 9, 'report_id' => 3, 'position_id' => 1, 'employee_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            (object) ['id' => 10, 'report_id' => 3, 'position_id' => 2, 'employee_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ];
        $rows = GiaoBanDutyService::copyRows($prev, 7);
        $this->assertSame([
            ['report_id' => 7, 'position_id' => 1, 'employee_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            ['report_id' => 7, 'position_id' => 2, 'employee_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ], $rows);
    }

    /** @test */
    public function copy_rows_empty_input_returns_empty()
    {
        $this->assertSame([], GiaoBanDutyService::copyRows([], 7));
    }

    /** @test */
    public function can_edit_admin_or_in_editor_list()
    {
        $this->assertTrue(GiaoBanDutyService::canEdit(true, [], 5));
        $this->assertTrue(GiaoBanDutyService::canEdit(false, [3, 5], 5));
        $this->assertFalse(GiaoBanDutyService::canEdit(false, [3, 5], 9));
    }
}
