<?php

namespace Tests\Unit\Xml3176;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Chot tep mau danh muc Phu luc I Thong tu 01/2025/TT-BYT (spec muc 5.2, Phu luc A).
 * Chep sai mot ma o day la quy tac 1.17 bao oan hoac bo sot - test khoa cac cho de sai.
 */
class MauDanhMucPl1Test extends TestCase
{
    private static $dong;

    private function dong()
    {
        if (self::$dong === null) {
            $ws = IOFactory::load(base_path('docs/0000 - Danh muc/PL1_TT01_2025.xlsx'))->getActiveSheet();
            $bang = $ws->toArray(null, true, false, false);
            $tieuDe = array_shift($bang);
            $this->assertSame(['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'], $tieuDe);

            self::$dong = array_map(function ($r) {
                return [
                    'stt' => (int) $r[0], 'ten' => (string) $r[1], 'ma' => (string) $r[2],
                    'loai' => (string) $r[3], 'tuoi' => ($r[4] === null || $r[4] === '') ? null : (int) $r[4],
                ];
            }, array_values(array_filter($bang, function ($r) { return $r[0] !== null && $r[0] !== ''; })));
        }

        return self::$dong;
    }

    private function cua($stt)
    {
        return array_values(array_filter($this->dong(), function ($d) use ($stt) { return $d['stt'] === $stt; }));
    }

    /** @test */
    public function du_186_dong_62_stt()
    {
        $d = $this->dong();
        $this->assertCount(186, $d);
        $this->assertSame(range(1, 62), array_values(array_unique(array_column($d, 'stt'))));
        $this->assertCount(182, array_filter($d, function ($x) { return $x['loai'] === 'BAO_GOM'; }));
    }

    /** @test */
    public function dung_4_dong_tru()
    {
        $tru = array_values(array_filter($this->dong(), function ($x) { return $x['loai'] === 'TRU'; }));
        $cap = array_map(function ($x) { return $x['stt'] . ':' . $x['ma']; }, $tru);
        sort($cap);
        $this->assertSame(['16:C38.4', '23:C83.5', '25:D61.9', '38:G04.2'], $cap);
    }

    /** @test */
    public function moi_ma_dung_dinh_dang_va_moi_loai_hop_le()
    {
        foreach ($this->dong() as $x) {
            $this->assertRegExp('/^[A-Z]\d{2}(\.\d)?$/', $x['ma'], 'STT ' . $x['stt']);
            $this->assertContains($x['loai'], ['BAO_GOM', 'TRU']);
            $this->assertNotSame('', trim($x['ten']), 'STT ' . $x['stt'] . ' thieu ten benh');
        }
    }

    /** @test */
    public function cac_khoang_ma_duoc_tach_du()
    {
        $ma22 = array_column($this->cua(22), 'ma');
        $this->assertCount(98, $ma22);
        $this->assertSame('C00', $ma22[0]);
        $this->assertSame('C97', end($ma22));

        $this->assertSame(
            ['C81', 'C82', 'C83', 'C84', 'C85', 'C86', 'C90', 'C91', 'C92', 'C93', 'C94', 'C95', 'C96', 'C83.5'],
            array_column($this->cua(23), 'ma')
        );
        $this->assertSame(['E74', 'E75', 'E76'], array_column($this->cua(33), 'ma'));
        $this->assertSame(['Q20', 'Q21', 'Q22', 'Q23', 'Q24', 'Q25', 'Q26', 'Q27', 'Q28'], array_column($this->cua(58), 'ma'));
    }

    /** @test */
    public function dong_44_co_ca_i51_2_va_l51_2()
    {
        $this->assertSame(['I51.2', 'L51.2'], array_column($this->cua(44), 'ma'));
    }

    /** @test */
    public function dieu_kien_tuoi_dung_5_stt()
    {
        $coTuoi = [];
        foreach ($this->dong() as $x) {
            if ($x['tuoi'] !== null) {
                $this->assertSame(18, $x['tuoi']);
                $coTuoi[$x['stt']] = true;
            }
        }
        $this->assertSame([22, 30, 31, 32, 58], array_keys($coTuoi));

        foreach ([22, 30, 31, 32, 58] as $stt) {
            foreach ($this->cua($stt) as $x) {
                $this->assertSame(18, $x['tuoi'], "Moi dong cua STT $stt phai co TUOI_DUOI = 18");
            }
        }
    }

    /** @test */
    public function mau_ma_cac_dong_don()
    {
        $moi = [];
        foreach ($this->dong() as $x) {
            if (count($this->cua($x['stt'])) === 1) {
                $moi[$x['stt']] = $x['ma'];
            }
        }

        $this->assertSame('A17.0', $moi[1]);
        $this->assertSame('C79.3', $moi[21]);
        $this->assertSame('E11.7', $moi[29]);
        $this->assertSame('I50', $moi[43]);
        $this->assertSame('M32.1', $moi[54]);
        $this->assertSame('Z94', $moi[62]);
    }
}
