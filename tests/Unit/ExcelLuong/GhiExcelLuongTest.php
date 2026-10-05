<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\GhiExcelLuong;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\SheetGia;
use Tests\TestCase;

class GhiExcelLuongTest extends TestCase
{
    protected $goc;
    protected $tamGoc;

    protected function setUp()
    {
        parent::setUp();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ghi_luong_', true);
        $this->tamGoc = $this->goc . DIRECTORY_SEPARATOR . 'tam';
        mkdir($this->tamGoc, 0777, true);
    }

    protected function tearDown()
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->goc, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($this->goc);
        parent::tearDown();
    }

    private function dich()
    {
        return $this->goc . DIRECTORY_SEPARATOR . 'ra' . DIRECTORY_SEPARATOR . 'tep.xlsx';
    }

    private function ghi(array $sheets)
    {
        (new GhiExcelLuong($this->tamGoc))->ghi($sheets, $this->dich());

        return IOFactory::load($this->dich());
    }

    /** @test */
    public function dung_so_sheet_ten_va_thu_tu()
    {
        $wb = $this->ghi([
            new SheetGia('XML1', ['A'], [[1]]),
            new SheetGia('Lỗi thẻ BHYT', ['A'], []),
            new SheetGia('DM khoa-giường', ['A'], [['x']]),
        ]);

        $this->assertSame(['XML1', 'Lỗi thẻ BHYT', 'DM khoa-giường'], $wb->getSheetNames());
    }

    /** Chan loi mat dong im lang kieu 'yield from' (05/10/2026): du dong qua nhieu lo. */
    /** @test */
    public function tieu_de_dong_1_va_du_dong_qua_nhieu_lo()
    {
        $dong = [];
        for ($i = 1; $i <= 2500; $i++) {
            $dong[] = [$i, 'LK' . $i];
        }

        $ws = $this->ghi([new SheetGia('S', ['STT', 'Mã LK'], $dong)])->getSheet(0);

        $this->assertSame('STT', $ws->getCell('A1')->getValue());
        $this->assertSame(2501, $ws->getHighestRow());
        $this->assertEquals(2500, $ws->getCell('A2501')->getValue());
        $this->assertSame('LK2500', $ws->getCell('B2501')->getValue());
    }

    /** @test */
    public function kieu_o_giong_tep_cu_tru_cong_thuc()
    {
        $ws = $this->ghi([
            new SheetGia('S', ['a', 'b', 'c', 'd'], [['202610050800', '000007230917', null, '=SUM(A1)']]),
            new SheetGia('DM', ['a'], [['123']], ['kieu_o' => 'chu']),
        ]);
        $s = $ws->getSheet(0);

        $this->assertSame('n', $s->getCell('A2')->getDataType());
        $this->assertEquals(202610050800, $s->getCell('A2')->getValue());
        $this->assertSame('s', $s->getCell('B2')->getDataType());
        $this->assertSame('000007230917', $s->getCell('B2')->getValue());
        $this->assertNull($s->getCell('C2')->getValue());
        $this->assertSame('s', $s->getCell('D2')->getDataType(), 'Chuoi = phai la chu, khong la cong thuc');
        $this->assertSame('=SUM(A1)', $s->getCell('D2')->getValue());
        $this->assertSame('s', $ws->getSheet(1)->getCell('A2')->getDataType());
        $this->assertSame('123', $ws->getSheet(1)->getCell('A2')->getValue());
    }

    /** @test */
    public function style_tieu_de_cot_so_xuong_dong_va_do_rong()
    {
        $wb = $this->ghi([
            new SheetGia('Loi', ['STT', 'Ngay'], [[1, '202610050800']], [
                'do_rong' => ['A' => 5, 'B' => 13], 'cot_so' => ['B'],
                'xuong_dong' => true, 'tieu_de_can_giua' => true,
            ]),
            new SheetGia('DM', ['MA'], [['K01']], ['kieu_o' => 'chu']),
        ]);
        $s = $wb->getSheet(0);
        $dm = $wb->getSheet(1);

        $this->assertTrue($s->getStyle('A1')->getFont()->getBold());
        $this->assertSame('center', $s->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertTrue($s->getStyle('A2')->getAlignment()->getWrapText());
        $this->assertSame('0', $s->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('General', $s->getStyle('A2')->getNumberFormat()->getFormatCode(),
            'Cot khong nam trong cot_so khong mang dinh dang 0');
        $this->assertEquals(5, $s->getColumnDimension('A')->getWidth());
        $this->assertEquals(13, $s->getColumnDimension('B')->getWidth());

        $this->assertTrue($dm->getStyle('A1')->getFont()->getBold());
        $this->assertNotSame('center', $dm->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertFalse($dm->getStyle('A2')->getAlignment()->getWrapText());
    }

    /** @test */
    public function hong_giua_chung_thi_khong_co_tep_dich_va_don_thu_muc_tam()
    {
        $dong = [];
        for ($i = 1; $i <= 2000; $i++) {
            $dong[] = [$i];
        }

        try {
            (new GhiExcelLuong($this->tamGoc))->ghi([new SheetGia('S', ['A'], $dong, [], 1500)], $this->dich());
            $this->fail('Phai nem lai loi');
        } catch (\RuntimeException $e) {
            $this->assertSame('hong giua chung', $e->getMessage());
        }

        $this->assertFileNotExists($this->dich());
        $this->assertSame([], glob($this->tamGoc . DIRECTORY_SEPARATOR . '*'), 'Thu muc tam phai duoc don');
    }

    /** @test */
    public function thanh_cong_cung_don_thu_muc_tam()
    {
        $this->ghi([new SheetGia('S', ['A'], [[1]])]);

        $this->assertSame([], glob($this->tamGoc . DIRECTORY_SEPARATOR . '*'));
    }
}
