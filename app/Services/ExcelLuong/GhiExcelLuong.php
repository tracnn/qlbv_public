<?php

namespace App\Services\ExcelLuong;

use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Ghi nhieu sheet ra MOT tep xlsx THEO LUONG (Spout 3.3): moi dong xuong dia ngay, RAM ~ mot lo
 * 1000 dong bat ke so dong.
 *
 * Vi sao: PhpSpreadsheet giu moi o trong RAM - do 50.000 dong x 21 cot = 666 MB; ngay 05/10/2026
 * (~358.800 dong loi XML3176) het 4096M. Spout cung 50.000 dong: 8 s, ~0 MB.
 *
 * Doc sheet qua NguonSheet nen dung lai NGUYEN cac lop export Laravel Excel san co (headings,
 * map, generator/query, dinhDangLuong) - cot chi dinh nghia mot noi.
 *
 * Khong bao gio de tep dich do dang: ghi vao thu muc tam rieng moi lan chay, xong moi rename;
 * finally luon xoa thu muc tam. Moi loi nem lai cho job.
 */
class GhiExcelLuong
{
    const FORMAT_DAU = '0.00';

    protected $thuMucTamGoc;

    public function __construct(string $thuMucTamGoc = null)
    {
        $this->thuMucTamGoc = $thuMucTamGoc ?: storage_path('app/xuat-tam');
    }

    /**
     * @param array $sheets lop export theo thu tu sheet (vd Xml3176ErrorMultiSheetExport::sheets())
     * @param string $dich duong dan tuyet doi tep xlsx dich
     */
    public function ghi(array $sheets, string $dich): void
    {
        $tam = $this->thuMucTamGoc . DIRECTORY_SEPARATOR . uniqid('xuat_', true);

        if (!is_dir($tam) && !mkdir($tam, 0777, true)) {
            throw new \RuntimeException("Không tạo được thư mục tạm $tam");
        }

        $writer = null;
        $daDong = false;

        try {
            $tepTam = $tam . DIRECTORY_SEPARATOR . 'tep.xlsx';

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->setTempFolder($tam);
            // Chuoi dung shared strings nhu tep Laravel Excel cu: PhpSpreadsheet doc inlineStr (mac dinh cua Spout)
            // thanh RichText, lech tep cu. SharedStringsManager cua Spout ghi thang ra tep tam nen RAM van phang.
            $writer->setShouldUseInlineStrings(false);
            $writer->openToFile($tepTam);

            $doRong = [];

            foreach (array_values($sheets) as $i => $export) {
                $nguon = new NguonSheet($export);

                if ($i > 0) {
                    $writer->addNewSheetAndMakeItCurrent();
                }

                $writer->getCurrentSheet()->setName($nguon->ten());
                $this->ghiSheet($writer, $nguon);
                $doRong[$i + 1] = $nguon->dinhDang()['do_rong'];
            }

            $writer->close();
            $daDong = true;

            $this->suaFormatSo($tepTam);

            (new ChenDoRongCot())->chen($tepTam, $doRong);

            $this->dua($tepTam, $dich);
        } finally {
            // Hong giua chung: dong writer de nha file handle (Windows khong xoa duoc tep dang mo).
            if ($writer !== null && !$daDong) {
                try {
                    $writer->close();
                } catch (\Throwable $e) {
                    // Loi goc dang duoc nem ra - loi dong khong che no.
                }
            }

            $this->xoaThuMuc($tam);
        }
    }

    protected function ghiSheet($writer, NguonSheet $nguon): void
    {
        $d = $nguon->dinhDang();

        $b = (new StyleBuilder())->setFontBold();
        if ($d['tieu_de_can_giua']) {
            $b->setCellAlignment(CellAlignment::CENTER);
        }
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $tieuDe = $b->build();

        $b = new StyleBuilder();
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $duLieu = $b->build();

        // Spout 3.3 bo qua format '0' (so sanh `if ($format)`, chuoi "0" la falsy) nen dung '0.00' (id 2)
        // lam dau, roi doi numFmtId 2 -> 1 trong styles.xml (suaFormatSo). Cac o khac khong dung '0.00'.
        $b = (new StyleBuilder())->setFormat(self::FORMAT_DAU);
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $duLieuSo = $b->build();

        $cotSo = [];
        foreach ($d['cot_so'] as $chu) {
            $cotSo[Coordinate::columnIndexFromString($chu) - 1] = true;
        }

        $writer->addRow(WriterEntityFactory::createRowFromArray($nguon->tieuDe(), $tieuDe));

        foreach ($nguon->dong() as $dong) {
            $o = [];

            foreach (array_values($dong) as $j => $v) {
                list($gia, $laSo) = KieuO::chuyen($v, $d['kieu_o']);

                $o[] = ($laSo && isset($cotSo[$j]))
                    ? WriterEntityFactory::createCell($gia, $duLieuSo)
                    : WriterEntityFactory::createCell($gia);
            }

            $writer->addRow(WriterEntityFactory::createRow($o, $duLieu));
        }
    }

    /** Doi numFmtId dau (2 = '0.00') thanh 1 (= '0') trong styles.xml - xem ghiSheet(). */
    protected function suaFormatSo(string $tepXlsx): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($tepXlsx) !== true) {
            throw new \RuntimeException('Không mở được tệp xlsx để sửa định dạng số');
        }

        $xml = $zip->getFromName('xl/styles.xml');
        $moi = $xml === false ? false : str_replace('<xf numFmtId="2" ', '<xf numFmtId="1" ', $xml);

        if ($moi === false || !$zip->addFromString('xl/styles.xml', $moi) || !$zip->close()) {
            throw new \RuntimeException('Không sửa được định dạng số của tệp xlsx');
        }
    }

    protected function dua(string $tepTam, string $dich): void
    {
        $thuMuc = dirname($dich);

        if (!is_dir($thuMuc) && !mkdir($thuMuc, 0777, true)) {
            throw new \RuntimeException("Không tạo được thư mục $thuMuc");
        }

        if (is_file($dich) && !unlink($dich)) {
            throw new \RuntimeException("Không xoá được tệp cũ $dich");
        }

        if (!rename($tepTam, $dich)) {
            throw new \RuntimeException('Không ghi được tệp xuất ra đĩa');
        }
    }

    protected function xoaThuMuc(string $thuMuc): void
    {
        if (!is_dir($thuMuc)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($thuMuc, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }

        @rmdir($thuMuc);
    }
}
