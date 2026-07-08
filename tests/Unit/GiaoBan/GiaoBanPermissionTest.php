<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanPermission;

class GiaoBanPermissionTest extends TestCase
{
    /** @test */
    public function admin_can_edit_any_dept_even_when_not_assigned()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(true, [], 5));
    }

    /** @test */
    public function khoa_user_can_edit_only_assigned_depts()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(false, [3, 5], 5));
        $this->assertFalse(GiaoBanPermission::canEditDept(false, [3, 5], 7));
    }

    /** @test */
    public function nobody_edits_when_report_is_final()
    {
        $this->assertFalse(GiaoBanPermission::canEditReport('final', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', false));
    }
}
