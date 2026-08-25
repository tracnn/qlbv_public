<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Exports\Tt12BieuMauExport;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Bieu mau Excel rong cho tung mau TT12 - chi hang tieu de, khong dong du lieu.
 *
 * Nguoi dung nap MAU_03 hien phai tu biet 37 cot ten gi va dung thu tu nao; bieu mau
 * tai ve giai quyet dung cai do nen phai KHOP CHINH XAC voi Mau0x::tenThe().
 */
class Tt12BieuMauExportTest extends TestCase
{
    public function cacMau()
    {
        return array(
            array('MAU_01', 11),
            array('MAU_02', 24),
            array('MAU_03', 37),
            array('MAU_04', 26),
            // MAU_05: 16 cot cha + 12 cot con (tien to THUOCPX_) doc trong CUNG mot sheet.
            array('MAU_05', 28),
            array('MAU_06', 14),
        );
    }

    /**
     * @test
     * @dataProvider cacMau
     */
    public function tieu_de_khop_dung_ten_the_va_dung_so_cot($mau, $soCot)
    {
        $lop = Tt12MauRegistry::cho($mau);
        $export = new Tt12BieuMauExport($mau);

        $tieuDe = $export->headings();

        $mongDoi = $lop::tenThe();
        if ($lop::cotCon() !== array()) {
            $mongDoi = array_merge($mongDoi, $lop::tenCotExcelCon());
        }

        $this->assertSame($mongDoi, $tieuDe, $mau . ': tieu de phai khop dung thu tu voi tenThe()');
        $this->assertCount($soCot, $tieuDe, $mau . ': sai so cot');
    }

    /** @test */
    public function khong_co_dong_du_lieu_nao()
    {
        $this->assertSame(array(), (new Tt12BieuMauExport('MAU_03'))->array());
    }

    /** @test */
    public function mau_la_bi_tu_choi()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Tt12BieuMauExport('MAU_KHONG_CO');
    }

    /** @test */
    public function cot_bat_buoc_duoc_to_mau_dung_theo_dac_ta_mau()
    {
        // Co bat_buoc da co san trong Mau0x::cot() - khong duoc khai lai danh sach nay o
        // dau khac, chi hoi lai dac ta.
        $lop = Tt12MauRegistry::cho('MAU_01');
        $export = new Tt12BieuMauExport('MAU_01');

        $mongDoi = array();
        foreach ($lop::cot() as $cot) {
            if ($cot['bat_buoc']) {
                $mongDoi[] = $cot['the'];
            }
        }

        $this->assertSame($mongDoi, $export->requiredHeaders());
        $this->assertNotEmpty($mongDoi);
    }

    /** @test */
    public function ma_cskcb_khong_can_mau_rieng_vi_da_bat_buoc()
    {
        // Khac CatalogTemplateExport: o TT12, MA_CSKCB la cot BAT BUOC nen no da nam
        // trong nhom bat buoc - khong can them mot mau thu hai.
        $export = new Tt12BieuMauExport('MAU_03');

        $this->assertContains('MA_CSKCB', $export->requiredHeaders());
    }

    /** @test */
    public function xuat_thanh_tep_xlsx_doc_lai_dung_tieu_de() // @test tich hop nhe
    {
        $export = new Tt12BieuMauExport('MAU_01');

        \Maatwebsite\Excel\Facades\Excel::store($export, 'tt12-bieu-mau-test.xlsx', 'local');

        $duongDan = storage_path('app/tt12-bieu-mau-test.xlsx');
        $this->assertFileExists($duongDan);

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($duongDan);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('STT', $sheet->getCell('A1')->getValue());
        $this->assertSame('MA_CSKCB', $sheet->getCell('K1')->getValue());
        // Hang 2 phai rong: khong dong du lieu nao.
        $this->assertNull($sheet->getCell('A2')->getValue());

        // A1 = STT (bat_buoc = true) phai duoc to nen; D1 = BAN_KHAM (bat_buoc = false)
        // thi khong. Kiem THANG mau to tren o, khong chi danh sach requiredHeaders(): mot
        // ham requiredHeaders() dung ma registerEvents() quen ap dung van la mot loi.
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            $sheet->getStyle('A1')->getFill()->getFillType(),
            'STT la cot bat buoc, phai duoc to nen'
        );
        $this->assertNotSame(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            $sheet->getStyle('D1')->getFill()->getFillType(),
            'BAN_KHAM khong bat buoc, khong duoc to nen'
        );

        unlink($duongDan);
    }
}
