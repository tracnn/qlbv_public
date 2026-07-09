<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\ViSearch;

class ViSearchTest extends TestCase
{
    /** @test */
    public function normalize_lowercases_and_strips_vietnamese_diacritics()
    {
        $this->assertSame('nguyen van a', ViSearch::normalize('Nguyễn Văn A'));
        $this->assertSame('do thi huong', ViSearch::normalize('Đỗ Thị Hương'));
        $this->assertSame('tran quoc', ViSearch::normalize('TRẦN QUỐC'));
    }

    /** @test */
    public function from_and_to_maps_have_equal_length()
    {
        $this->assertSame(mb_strlen(ViSearch::FROM), mb_strlen(ViSearch::TO));
    }

    /** @test */
    public function no_diacritics_sql_wraps_translate_lower()
    {
        $sql = ViSearch::noDiacriticsSql('tdl_username');
        $this->assertContains('TRANSLATE(LOWER(tdl_username)', $sql);
    }
}
