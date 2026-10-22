<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\GhiTep;
use App\Services\SinhXml3176\LoiGhiTep;
use PHPUnit\Framework\TestCase;

class GhiTepTest extends TestCase
{
    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_ghitep_' . uniqid();
    }

    protected function tearDown()
    {
        foreach ((array) glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $f) {
            is_dir($f) ? $this->xoaThuMucCon($f) : @unlink($f);
        }
        is_dir($this->thuMuc) ? @rmdir($this->thuMuc) : @unlink($this->thuMuc);
        parent::tearDown();
    }

    public function test_ten_tep_chinh_thuc_va_tt()
    {
        $luc = new \DateTime('2026-10-22 15:30:00');
        $this->assertSame('QLBV_20261022153000_000123.xml', GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', $luc));
        $this->assertSame('QLBV_20261022153000_000123_TT.xml', GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::THONG_TUYEN, 'b', $luc));
    }

    public function test_khong_de_lai_tep_tmp_va_tep_xml_day_du()
    {
        $luc = new \DateTime('2026-10-22 15:30:00');
        $nd = str_repeat('<a>x</a>', 1000);
        $ten = GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, $nd, $luc);
        $this->assertSame([$ten], array_map('basename', glob($this->thuMuc . DIRECTORY_SEPARATOR . '*')));
        $this->assertSame($nd, file_get_contents($this->thuMuc . DIRECTORY_SEPARATOR . $ten));

        // ghi de len tep da co
        GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'moi', $luc);
        $this->assertSame('moi', file_get_contents($this->thuMuc . DIRECTORY_SEPARATOR . $ten));
        $this->assertCount(1, glob($this->thuMuc . DIRECTORY_SEPARATOR . '*'));
    }

    public function test_loai_ban_la_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);
        GhiTep::ghi($this->thuMuc, '000123', 'TT', 'a', new \DateTime('2026-10-22 15:30:00'));
    }
    private function xoaThuMucCon($d)
    {
        foreach ((array) glob($d . DIRECTORY_SEPARATOR . '*') as $f) {
            @unlink($f);
        }
        @rmdir($d);
    }

    private function nemLoiGhiTep(callable $f, $chua)
    {
        try {
            $f();
            $this->fail('Phai nem LoiGhiTep');
        } catch (LoiGhiTep $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertContains($chua, $e->getMessage());
        }
    }

    public function test_khong_tao_duoc_thu_muc_nem_loi_ghi_tep()
    {
        file_put_contents($this->thuMuc, 'la tep, khong phai thu muc');
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, 'thu muc');
    }

    public function test_khong_ghi_duoc_tep_tam_nem_loi_ghi_tep()
    {
        mkdir($this->thuMuc . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.tmp', 0775, true);
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, '.tmp');
    }

    public function test_khong_doi_ten_duoc_nem_loi_ghi_tep_va_xoa_tmp()
    {
        $dich = $this->thuMuc . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.xml';
        mkdir($dich, 0775, true);
        file_put_contents($dich . DIRECTORY_SEPARATOR . 'chan', 'x');
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, 'doi ten');
        $this->assertFileNotExists($this->thuMuc . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.tmp');
    }
}
