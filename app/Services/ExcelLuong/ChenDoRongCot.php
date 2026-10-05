<?php

namespace App\Services\ExcelLuong;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Chen do rong cot (<cols>) vao tep xlsx Spout da ghi - Spout 3.3 khong dat duoc do rong.
 *
 * Spout ghi sheet thu n vao xl/worksheets/sheet{n}.xml, phan dau la '<worksheet ...><sheetData>';
 * thu tu OOXML: <cols> dung truoc <sheetData>. Chep THEO LUONG sang tep tam (sheet XML3 co the
 * > 100 MB) roi thay muc trong zip - khong nap ca sheet vao RAM.
 */
class ChenDoRongCot
{
    /** Chi tim <sheetData trong phan dau nay; khong thay = Spout doi dinh dang -> nem. */
    const TIM_TRONG = 65536;

    const KHOI = 1048576;

    /**
     * @param array $doRongTheoSheet [so thu tu sheet (tu 1) => ['A' => 5, ...]]
     */
    public function chen(string $tepXlsx, array $doRongTheoSheet): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($tepXlsx) !== true) {
            throw new \RuntimeException("Không mở được tệp xlsx $tepXlsx");
        }

        $tepTam = [];

        try {
            foreach ($doRongTheoSheet as $so => $doRong) {
                if (!$doRong) {
                    continue;
                }

                $ten = "xl/worksheets/sheet{$so}.xml";
                $tam = tempnam(dirname($tepXlsx), 'cols');
                $tepTam[] = $tam;

                $this->chepCoCols($zip, $ten, $tam, $this->xmlCols($doRong));

                if (!$zip->addFile($tam, $ten)) {
                    throw new \RuntimeException("Không thay được $ten trong tệp xlsx");
                }
            }

            if (!$zip->close()) {
                throw new \RuntimeException('Không ghi lại được tệp xlsx sau khi chèn độ rộng cột');
            }
            $zip = null;
        } finally {
            // addFile() doc tep tam LUC close() - chi xoa sau khi da close.
            if ($zip === null) {
                foreach ($tepTam as $t) {
                    @unlink($t);
                }
            }
        }
    }

    protected function chepCoCols(\ZipArchive $zip, string $ten, string $tam, string $cols): void
    {
        $vao = $zip->getStream($ten);

        if ($vao === false) {
            throw new \RuntimeException("Không thấy $ten trong tệp xlsx");
        }

        $ra = fopen($tam, 'wb');

        try {
            $dau = '';
            while (!feof($vao) && strlen($dau) < self::TIM_TRONG && strpos($dau, '<sheetData') === false) {
                $dau .= fread($vao, 8192);
            }

            $vt = strpos($dau, '<sheetData');

            if ($vt === false) {
                throw new \RuntimeException("Không thấy <sheetData> trong $ten - định dạng Spout đã đổi?");
            }

            fwrite($ra, substr($dau, 0, $vt) . $cols . substr($dau, $vt));

            while (!feof($vao)) {
                fwrite($ra, fread($vao, self::KHOI));
            }
        } finally {
            fclose($vao);
            fclose($ra);
        }
    }

    protected function xmlCols(array $doRong): string
    {
        $theoSo = [];

        foreach ($doRong as $chu => $rong) {
            $theoSo[Coordinate::columnIndexFromString($chu)] = $rong;
        }

        ksort($theoSo);

        $xml = '<cols>';

        foreach ($theoSo as $i => $rong) {
            $xml .= '<col min="' . $i . '" max="' . $i . '" width="' . $this->so($rong) . '" customWidth="1"/>';
        }

        return $xml . '</cols>';
    }

    /** So khong phu thuoc locale (PHP 7.4 doi float sang chuoi theo LC_NUMERIC). */
    protected function so($rong): string
    {
        if (is_int($rong)) {
            return (string) $rong;
        }

        return rtrim(rtrim(sprintf('%.4F', $rong), '0'), '.');
    }
}
