<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MaDvktMatcher;
use Tests\TestCase;

class MaDvktMatcherTest extends TestCase
{
    /** @test */
    public function ma_thuong_giu_nguyen()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319'));
    }

    /** @test */
    public function bo_hau_to_sau_gach_duoi()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319_TB'));
    }

    /** @test */
    public function bo_tu_dau_gach_duoi_dau_tien()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319_TB_XX'));
    }

    /** @test */
    public function cat_khoang_trang()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('  02.0261.0319_TB  '));
    }

    /** @test */
    public function chuoi_rong_va_null()
    {
        $this->assertSame('', MaDvktMatcher::maGoc(''));
        $this->assertSame('', MaDvktMatcher::maGoc(null));
    }

    /** @test */
    public function ma_bat_dau_bang_gach_duoi_tra_rong()
    {
        $this->assertSame('', MaDvktMatcher::maGoc('_TB'));
    }
}
