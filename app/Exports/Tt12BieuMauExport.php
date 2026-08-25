<?php

namespace App\Exports;

use App\Services\Tt12\Tt12MauRegistry;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Bieu mau Excel RONG cho mot mau danh muc TT12/2026/BTC - chi hang tieu de, khong dong
 * du lieu, de nguoi dung dien vao roi nap lai.
 *
 * Khuon theo CatalogTemplateExport, nhung khac o MOT diem: MA_CSKCB o TT12 la cot BAT
 * BUOC (khac danh muc thu cong, noi bo trong no la "dung chung moi co so" mot cach im
 * lang) nen no da nam trong nhom bat buoc - khong to them mau rieng cho no.
 */
class Tt12BieuMauExport implements FromArray, WithHeadings, WithEvents
{
    protected $mau;
    protected $lop;

    /**
     * @param string $mau vi du 'MAU_03'
     * @throws \InvalidArgumentException khi mau khong nam trong Tt12MauRegistry
     */
    public function __construct($mau)
    {
        // Uy thac cho Tt12MauRegistry::cho() thay vi tu kiem roi nem: mot noi duy nhat
        // quyet dinh mau nao hop le, dung nhu moi noi khac trong module nay dang hoi.
        $this->lop = Tt12MauRegistry::cho($mau);
        $this->mau = $mau;
    }

    /**
     * Tieu de = ten the cot cha, VA voi MAU_05 noi them 12 cot bang con (tien to
     * THUOCPX_): bo nap doc bang con TRONG CUNG mot sheet, nen bieu mau thieu chung la
     * bieu mau nap vao se mat du lieu thuoc phong xa.
     *
     * @return array
     */
    public function headings(): array
    {
        $lop = $this->lop;
        $tieuDe = $lop::tenThe();

        if ($lop::cotCon() !== array()) {
            $tieuDe = array_merge($tieuDe, $lop::tenCotExcelCon());
        }

        return $tieuDe;
    }

    /** @return array ten cac cot co bat_buoc = true trong dac ta mau */
    public function requiredHeaders()
    {
        $bat_buoc = array();

        foreach ($this->lop::cot() as $cot) {
            if ($cot['bat_buoc']) {
                $bat_buoc[] = $cot['the'];
            }
        }

        return $bat_buoc;
    }

    /** Khong co dong du lieu - chi header cho nguoi dung dien. */
    public function array(): array
    {
        return array();
    }

    public function registerEvents(): array
    {
        return array(
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $tieuDe = $this->headings();
                $batBuoc = $this->requiredHeaders();

                foreach ($tieuDe as $i => $ten) {
                    $cot = Coordinate::stringFromColumnIndex($i + 1);
                    $o = $cot . '1';

                    $sheet->getStyle($o)->getFont()->setBold(true);
                    $sheet->getColumnDimension($cot)->setWidth(max(16, mb_strlen((string) $ten) + 6));

                    if (in_array($ten, $batBuoc, true)) {
                        $sheet->getStyle($o)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFF2CC');
                    }
                }
            },
        );
    }
}
