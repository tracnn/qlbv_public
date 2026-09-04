<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TextNormalizer;
use Tests\TestCase;

class TextNormalizerTest extends TestCase
{
    /** @test */
    public function chuan_trim_gop_khoang_trang_ha_chu_thuong()
    {
        $this->assertEquals('a b c', TextNormalizer::chuan("  A   B\tC "));
    }

    /** @test */
    public function chuan_null_tra_chuoi_rong()
    {
        $this->assertEquals('', TextNormalizer::chuan(null));
    }

    /** @test */
    public function chuan_hai_chuoi_khac_khoang_trang_hoa_thuong_thi_bang_nhau()
    {
        $this->assertEquals(
            TextNormalizer::chuan('Diễn Biến  ổn định'),
            TextNormalizer::chuan('diễn biến ổn định')
        );
    }
}
