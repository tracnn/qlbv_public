<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use App\Exports\HeinCardErrorExport;
use App\Exports\Xml3176ErrorSheetExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\FakeKhoaDieuTriHis;
use Tests\TestCase;

/**
 * dinhDangLuong() la nguon DUY NHAT cua dinh dang: duong Laravel Excel cu (registerEvents)
 * va duong ghi luong (GhiExcelLuong) cung doc. Test nay chay that registerEvents() tren mot
 * worksheet trong va doi chieu voi dinhDangLuong() - lech la hai duong ra tep khac nhau.
 */
class DinhDangLuongTest extends TestCase
{
    private function apSuKien($export)
    {
        $ws = (new Spreadsheet())->getActiveSheet();
        $su = $export->registerEvents()[AfterSheet::class];
        $su(new AfterSheet(new Sheet($ws), $export));

        return $ws;
    }

    private function loc()
    {
        return ['date_from' => '2026-10-05 00:00:00', 'date_to' => '2026-10-05 23:59:59'];
    }

    /** @test */
    public function sheet_loi_dinh_dang_khop_registerEvents()
    {
        $x = new Xml3176ErrorSheetExport('XML3', $this->loc(), [], new FakeKhoaDieuTriHis());
        $d = $x->dinhDangLuong();

        $this->assertSame(Xml3176ErrorSheetExport::DO_RONG, $d['do_rong']);
        $this->assertSame(Xml3176ErrorSheetExport::COT_NGAY, $d['cot_so']);
        $this->assertSame('tu_dong', $d['kieu_o']);
        $this->assertTrue($d['xuong_dong']);
        $this->assertTrue($d['tieu_de_can_giua']);

        $ws = $this->apSuKien($x);
        foreach ($d['do_rong'] as $cot => $rong) {
            $this->assertEquals($rong, $ws->getColumnDimension($cot)->getWidth(), "do rong cot $cot");
        }
        foreach ($d['cot_so'] as $cot) {
            $ws->setCellValue($cot . '2', 202610050800);
            $this->assertSame('0', $ws->getStyle($cot . '2')->getNumberFormat()->getFormatCode(), "cot so $cot");
        }
    }

    /** @test */
    public function sheet_the_dinh_dang_khop_registerEvents_ca_hai_che_do()
    {
        foreach ([true => 'I', false => 'F'] as $coMaKhoa => $cotCuoi) {
            $x = new HeinCardErrorExport('2026-10-01 00:00:00', '2026-10-05 23:59:59', null,
                $coMaKhoa ? 'xml3176' : 'qd130xml', $coMaKhoa, new FakeKhoaDieuTriHis());
            $d = $x->dinhDangLuong();

            $this->assertSame($cotCuoi, array_keys($d['do_rong'])[count($d['do_rong']) - 1]);
            $this->assertSame([], $d['cot_so']);
            $this->assertSame('tu_dong', $d['kieu_o']);
            $this->assertTrue($d['xuong_dong']);
            $this->assertTrue($d['tieu_de_can_giua']);

            $ws = $this->apSuKien($x);
            foreach ($d['do_rong'] as $cot => $rong) {
                $this->assertEquals($rong, $ws->getColumnDimension($cot)->getWidth(), "do rong cot $cot");
            }
        }
    }

    /** Hai sheet danh muc: StringValueBinder, chi tieu de dam, khong do rong, khong xuong dong. */
    /** @test */
    public function sheet_danh_muc_toan_chu_khong_do_rong()
    {
        foreach ([new DmKhoaGiuongSheetExport(null, []), new DmNvytSheetExport(null, [])] as $x) {
            $this->assertSame([
                'do_rong' => [], 'cot_so' => [], 'kieu_o' => 'chu',
                'xuong_dong' => false, 'tieu_de_can_giua' => false,
            ], $x->dinhDangLuong());
        }
    }
}
