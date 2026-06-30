<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\Duplicates;

class DuplicatesTest extends TestCase
{
    public function test_gom_nhom_trung_bo_qua_key_rong()
    {
        $items = [
            (object) ['k' => 'A', 'n' => 'x1'],
            (object) ['k' => 'A', 'n' => 'x2'],
            (object) ['k' => 'B', 'n' => 'y1'],
            (object) ['k' => '', 'n' => 'z1'],
            (object) ['k' => '', 'n' => 'z2'],
        ];
        $groups = Duplicates::groupsWithCountAbove($items, function ($i) { return $i->k; }, 1);
        // Chỉ nhóm 'A' (2 phần tử) vượt ngưỡng; key rỗng bị bỏ qua
        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('A', $groups);
        $this->assertCount(2, $groups['A']);
    }

    public function test_khong_co_trung_tra_rong()
    {
        $items = [(object) ['k' => 'A'], (object) ['k' => 'B']];
        $groups = Duplicates::groupsWithCountAbove($items, function ($i) { return $i->k; }, 1);
        $this->assertCount(0, $groups);
    }
}
