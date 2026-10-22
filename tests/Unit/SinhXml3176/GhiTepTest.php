<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\GhiTep;
use App\Services\SinhXml3176\LoiGhiTep;
use PHPUnit\Framework\TestCase;

class GhiTepTest extends TestCase
{
    private $goc;
    private $thuMuc;
    private $tam;

    protected function setUp()
    {
        parent::setUp();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_ghitep_' . uniqid();
        mkdir($this->goc, 0775, true);
        $this->thuMuc = $this->goc . DIRECTORY_SEPARATOR . 'dich';
        $this->tam = $this->goc . DIRECTORY_SEPARATOR . GhiTep::TEN_THU_MUC_TAM;
    }

    protected function tearDown()
    {
        $this->xoa($this->goc);
        parent::tearDown();
    }

    private function xoa($p)
    {
        if (is_dir($p)) {
            foreach (scandir($p) as $f) {
                if ($f !== '.' && $f !== '..') {
                    $this->xoa($p . DIRECTORY_SEPARATOR . $f);
                }
            }
            @rmdir($p);
        } elseif (file_exists($p)) {
            @unlink($p);
        }
    }

    /** Moi tep (de quy) trong thu muc dich, ten tuong doi. */
    private function tepTrongDich()
    {
        if (!is_dir($this->thuMuc)) {
            return [];
        }
        $ra = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->thuMuc, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $ra[] = substr($f->getPathname(), strlen($this->thuMuc) + 1);
        }
        sort($ra);

        return $ra;
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
        $this->assertSame([$ten], $this->tepTrongDich());
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
        mkdir($this->tam . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.tmp', 0775, true);
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, '.tmp');
        $this->assertSame([], $this->tepTrongDich());
    }

    public function test_khong_tao_duoc_thu_muc_tam_nem_loi_ghi_tep()
    {
        file_put_contents($this->tam, 'chan');
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, 'thu muc tam');
        $this->assertSame([], $this->tepTrongDich());
    }

    public function test_tep_tam_ghi_ngoai_thu_muc_dich()
    {
        $this->assertSame($this->tam, GhiTep::thuMucTamMacDinh($this->thuMuc));
        $this->assertSame($this->tam, GhiTep::thuMucTamMacDinh($this->thuMuc . DIRECTORY_SEPARATOR));

        $ten = GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));

        $this->assertSame([$ten], $this->tepTrongDich());
        $this->assertTrue(is_dir($this->tam));
        $this->assertSame([], glob($this->tam . DIRECTORY_SEPARATOR . '*'));
    }

    public function test_thu_muc_tam_khai_rieng()
    {
        $rieng = $this->goc . DIRECTORY_SEPARATOR . 'tam_rieng';
        $ten = GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'), $rieng);

        $this->assertSame([$ten], $this->tepTrongDich());
        $this->assertTrue(is_dir($rieng));
        $this->assertFalse(file_exists($this->tam));
    }

    public function test_thu_muc_tam_nam_trong_dich_bi_tu_choi()
    {
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'),
                $this->thuMuc . DIRECTORY_SEPARATOR . 'tam');
        }, 'nam trong thu muc dich');
        $this->assertSame([], $this->tepTrongDich());
    }

    public function test_khong_doi_ten_duoc_nem_loi_ghi_tep_va_xoa_tmp()
    {
        $dich = $this->thuMuc . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.xml';
        mkdir($dich, 0775, true);
        file_put_contents($dich . DIRECTORY_SEPARATOR . 'chan', 'x');
        $this->nemLoiGhiTep(function () {
            GhiTep::ghi($this->thuMuc, '000123', CauHinhSinhXml3176::CHINH_THUC, 'a', new \DateTime('2026-10-22 15:30:00'));
        }, 'doi ten');
        $this->assertFileNotExists($this->tam . DIRECTORY_SEPARATOR . 'QLBV_20261022153000_000123.tmp');
        // Trong thu muc dich chi con thu muc chan san co, khong co tep tam nao
        $this->assertSame(['QLBV_20261022153000_000123.xml' . DIRECTORY_SEPARATOR . 'chan'], $this->tepTrongDich());
    }
}
