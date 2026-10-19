<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use Illuminate\Support\Facades\Schema;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01BangTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
    }

    private function ban($cskcb, $soBan)
    {
        return Bh01Ban::create([
            'ma_ban'   => 'BH01_' . $cskcb . '_202609_' . $soBan,
            'ma_cskcb' => $cskcb,
            'nam_qt'   => '2026',
            'thang_qt' => '09',
            'so_ban'   => $soBan,
        ]);
    }

    /** @test */
    public function dung_duoc_ba_bang_va_bang_nguon()
    {
        foreach (['bh01_ban', 'bh01_dong', 'bh01_lich_su_gui', 'xml3176_xml1s'] as $bang) {
            $this->assertTrue(Schema::hasTable($bang), $bang);
        }
    }

    /** @test */
    public function khong_cho_hai_ban_cung_so_trong_mot_ky()
    {
        $this->ban('01929', 1);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Bh01Ban::create([
            'ma_ban' => 'BH01_01929_202609_1_TRUNG', 'ma_cskcb' => '01929',
            'nam_qt' => '2026', 'thang_qt' => '09', 'so_ban' => 1,
        ]);
    }

    /** @test */
    public function cung_so_ban_khac_co_so_thi_duoc()
    {
        $this->ban('01929', 1);
        $this->ban('37470', 1);

        $this->assertSame(2, Bh01Ban::count());
    }
}
