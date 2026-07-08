<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Models\GiaoBan\GiaoBanDeptConfig;

class GiaoBanDeptConfigTest extends TestCase
{
    /** @test */
    public function his_department_ids_parses_json_array_of_ints()
    {
        $c = new GiaoBanDeptConfig(['his_department_ids' => '[73, 54]']);
        $this->assertSame([73, 54], $c->hisDepartmentIds());
    }

    /** @test */
    public function his_department_ids_falls_back_to_legacy_single_column()
    {
        $c = new GiaoBanDeptConfig(['his_department_id' => 27]);
        $this->assertSame([27], $c->hisDepartmentIds());
    }

    /** @test */
    public function his_department_ids_empty_when_nothing_set()
    {
        $c = new GiaoBanDeptConfig([]);
        $this->assertSame([], $c->hisDepartmentIds());
    }
}
