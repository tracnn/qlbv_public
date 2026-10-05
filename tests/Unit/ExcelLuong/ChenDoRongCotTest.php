<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\ChenDoRongCot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ChenDoRongCotTest extends TestCase
{
    protected $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('chen_cols_', true);
        mkdir($this->thuMuc);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $f) {
            @unlink($f);
        }
        @rmdir($this->thuMuc);
        parent::tearDown();
    }

    /** Tep 2 sheet ghi bang Spout - dung dinh dang that ma GhiExcelLuong se tao. */
    private function tepSpout()
    {
        $tep = $this->thuMuc . DIRECTORY_SEPARATOR . 'a.xlsx';
        $w = WriterEntityFactory::createXLSXWriter();
        $w->setTempFolder($this->thuMuc);
        $w->openToFile($tep);
        $w->addRow(WriterEntityFactory::createRowFromArray(['a', 'b', 'c']));
        $w->addNewSheetAndMakeItCurrent();
        $w->addRow(WriterEntityFactory::createRowFromArray(['x']));
        $w->close();

        return $tep;
    }

    private function docMuc($tep, $ten)
    {
        $z = new \ZipArchive();
        $z->open($tep);
        $nd = $z->getFromName($ten);
        $z->close();

        return $nd;
    }

    /** @test */
    public function chen_cols_ngay_truoc_sheetData_va_khong_dung_sheet_khong_co_do_rong()
    {
        $tep = $this->tepSpout();
        $sheet2Truoc = $this->docMuc($tep, 'xl/worksheets/sheet2.xml');

        (new ChenDoRongCot())->chen($tep, [1 => ['C' => 50, 'A' => 5], 2 => []]);

        $this->assertContains(
            '<cols><col min="1" max="1" width="5" customWidth="1"/><col min="3" max="3" width="50" customWidth="1"/></cols><sheetData>',
            $this->docMuc($tep, 'xl/worksheets/sheet1.xml')
        );
        $this->assertSame($sheet2Truoc, $this->docMuc($tep, 'xl/worksheets/sheet2.xml'));
    }

    /** @test */
    public function tep_sau_chen_van_hop_le_va_do_rong_dung()
    {
        $tep = $this->tepSpout();

        (new ChenDoRongCot())->chen($tep, [1 => ['A' => 5, 'C' => 50]]);

        $ws = IOFactory::load($tep)->getSheet(0);
        $this->assertEquals(5, $ws->getColumnDimension('A')->getWidth());
        $this->assertEquals(50, $ws->getColumnDimension('C')->getWidth());
        $this->assertEquals('c', (string)$ws->getCell('C1')->getValue());
    }

    /** Spout doi dinh dang ghi ma khong ai biet: phai NEM, khong xuat tep thieu do rong im lang. */
    /** @test */
    public function khong_thay_sheetData_thi_nem_loi()
    {
        $tep = $this->thuMuc . DIRECTORY_SEPARATOR . 'b.xlsx';
        $z = new \ZipArchive();
        $z->open($tep, \ZipArchive::CREATE);
        $z->addFromString('xl/worksheets/sheet1.xml', '<worksheet>' . str_repeat(' ', 70000) . '</worksheet>');
        $z->close();

        $this->expectException(\RuntimeException::class);
        (new ChenDoRongCot())->chen($tep, [1 => ['A' => 5]]);
    }
}
