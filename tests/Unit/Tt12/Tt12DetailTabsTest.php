<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12DetailTabs;

class Tt12DetailTabsTest extends TestCase
{
    /** @test */
    public function co_bon_tab_ke_ca_nhat_ky_gui()
    {
        $tab = Tt12DetailTabs::cacTab();

        $this->assertArrayHasKey('lich_su', $tab);
        $this->assertSame('Nhật ký gửi', $tab['lich_su']);
        $this->assertCount(4, $tab);
    }

    /** @test */
    public function coTab_nhan_dien_duoc_lich_su()
    {
        $this->assertTrue(Tt12DetailTabs::coTab('lich_su'));
    }
}
