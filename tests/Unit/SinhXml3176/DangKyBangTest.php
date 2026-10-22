<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\DangKyBang;
use PHPUnit\Framework\TestCase;

class DangKyBangTest extends TestCase
{
    public function test_dot_1_chua_du()
    {
        $dk = DangKyBang::macDinh();
        $this->assertFalse($dk->daDu());
        $this->assertSame(['XML4', 'XML5', 'XML6', 'XML7', 'XML8', 'XML9', 'XML10', 'XML11', 'XML13', 'XML14', 'XML15'], $dk->thieu());
        $this->assertCount(11, $dk->thieu());
        $loai = array_map(function ($d) { return $d->loai(); }, $dk->cacDung());
        $this->assertSame(['XML2', 'XML3', 'XML1'], $loai);
    }

    public function test_du_14_bang()
    {
        $dung = [];
        foreach (['XML1','XML2','XML3','XML4','XML5','XML6','XML7','XML8','XML9','XML10','XML11','XML13','XML14','XML15'] as $l) {
            $dung[] = new BangGia($l);
        }
        $dk = new DangKyBang($dung);
        $this->assertTrue($dk->daDu());
        $this->assertSame([], $dk->thieu());
        $ds = $dk->cacDung();
        $this->assertSame('XML1', end($ds)->loai());
    }
}

class BangGia implements \App\Services\SinhXml3176\Bang\DungBang
{
    private $l;
    public function __construct($l) { $this->l = $l; }
    public function loai(): string { return $this->l; }
    public function coTrongHoSo(\App\Services\SinhXml3176\DuLieuHoSo $d): bool { return false; }
    public function dung(\App\Services\SinhXml3176\DuLieuHoSo $d, \App\Services\SinhXml3176\Nguon\HisDanhMuc $dm, array $daDung): array { return []; }
}
