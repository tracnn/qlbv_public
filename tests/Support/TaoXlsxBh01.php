<?php

namespace Tests\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as NgayExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Dung tep .xlsx tam cho test nhap Excel 01/BH.
 *
 * Kieu o giu DUNG kieu PHP cua gia tri: chuoi -> o chu, int/float -> o so, DateTime -> o ngay.
 * Phai phan biet vi loi that cua tep Excel nam o cho nay (o so 1929 thay vi chu '01929').
 * Chuoi bat dau bang '=' duoc ghi thanh CONG THUC (PhpSpreadsheet tinh san gia tri luu kem, vd =NA()
 * luu o loi t="e" #N/A) - de gia lap o loi cong thuc cua tep that.
 * Tep nam trong thu muc tam rieng, tu xoa o tearDown.
 */
trait TaoXlsxBh01
{
    private $thuMucXlsxBh01;

    /**
     * @param array  $hang      mang cac dong; moi dong la mang gia tri theo cot
     * @param string $tenSheet
     * @return string duong dan tep
     */
    protected function taoXlsx(array $hang, $tenSheet = 'C79')
    {
        if ($this->thuMucXlsxBh01 === null) {
            $this->thuMucXlsxBh01 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bh01xlsx_' . uniqid('', true);
            mkdir($this->thuMucXlsxBh01, 0777, true);
        }

        $bang = new Spreadsheet();
        $sheet = $bang->getActiveSheet();
        $sheet->setTitle($tenSheet);

        $r = 0;
        foreach ($hang as $dong) {
            $r++;
            $c = 0;
            foreach ($dong as $giaTri) {
                $c++;
                $o = $sheet->getCellByColumnAndRow($c, $r);
                if ($giaTri instanceof \DateTimeInterface) {
                    $o->setValue(NgayExcel::PHPToExcel($giaTri));
                    $o->getStyle()->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                } elseif (is_string($giaTri) && substr($giaTri, 0, 1) === '=') {
                    $o->setValue($giaTri);
                } elseif (is_string($giaTri)) {
                    $o->setValueExplicit($giaTri, DataType::TYPE_STRING);
                } elseif ($giaTri !== null) {
                    $o->setValue($giaTri);
                }
            }
        }

        $duongDan = $this->thuMucXlsxBh01 . DIRECTORY_SEPARATOR . uniqid('t', true) . '.xlsx';
        (new Xlsx($bang))->save($duongDan);

        return $duongDan;
    }

    protected function tearDown()
    {
        if ($this->thuMucXlsxBh01 !== null && is_dir($this->thuMucXlsxBh01)) {
            foreach (glob($this->thuMucXlsxBh01 . DIRECTORY_SEPARATOR . '*') as $tep) {
                @unlink($tep);
            }
            @rmdir($this->thuMucXlsxBh01);
        }
        $this->thuMucXlsxBh01 = null;

        parent::tearDown();
    }
}
