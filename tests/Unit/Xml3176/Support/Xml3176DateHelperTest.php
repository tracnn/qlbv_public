<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\Xml3176DateHelper;
use Tests\TestCase;

class Xml3176DateHelperTest extends TestCase
{
    /** @test */
    public function to_datetime_parse_do_rong_12_14_8()
    {
        $this->assertNotNull(Xml3176DateHelper::toDateTime('202607011530'));
        $this->assertNotNull(Xml3176DateHelper::toDateTime('20260701153059'));
        $this->assertNotNull(Xml3176DateHelper::toDateTime('20260701'));
    }

    /** @test */
    public function to_datetime_null_khi_khong_hop_le()
    {
        foreach ([null, '', '2026070', 'abcdefghijkl', '00000000', '202613011530'] as $x) {
            $this->assertNull(Xml3176DateHelper::toDateTime($x), 'phai null: ' . var_export($x, true));
        }
    }

    /** @test */
    public function date_part_tra_8_ky_tu()
    {
        $this->assertEquals('20260701', Xml3176DateHelper::datePart('202607011530'));
        $this->assertNull(Xml3176DateHelper::datePart('xxx'));
    }

    /** @test */
    public function diff_minutes_va_diff_days()
    {
        $this->assertEquals(2, Xml3176DateHelper::diffMinutes('202607010900', '202607010902'));
        $this->assertEquals(-2, Xml3176DateHelper::diffMinutes('202607010902', '202607010900'));
        $this->assertNull(Xml3176DateHelper::diffMinutes('xxx', '202607010900'));
        $this->assertEquals(3, Xml3176DateHelper::diffDays('202607010000', '202607040000'));
    }
}
