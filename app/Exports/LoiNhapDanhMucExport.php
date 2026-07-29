<?php

namespace App\Exports;

use App\Services\Import\KetQuaNhapDanhMuc;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Xuat danh sach dong hong cua mot lan nhap danh muc ra Excel.
 *
 * Truoc day nut "Chi tiet" sinh CSV o trinh duyet. Excel mo CSV thi hay tu doan kieu du
 * lieu: so dong bi coi la so, ma nhu '01929' mat so 0 dan dau. Xuat .xlsx thi giu nguyen
 * chuoi va co dinh dang.
 */
class LoiNhapDanhMucExport implements FromArray, WithHeadings, WithEvents
{
    /** @var array [['dong' => int, 'loai' => string, 'ly_do' => string], ...] */
    protected $dongLoi;

    /** @var string ten tep goc, de nguoi doc biet bao cao nay cua tep nao */
    protected $tenTep;

    public function __construct(array $dongLoi, $tenTep = '')
    {
        $this->dongLoi = $dongLoi;
        $this->tenTep = $tenTep;
    }

    public function headings(): array
    {
        return ['Dòng Excel', 'Loại', 'Lý do'];
    }

    public function array(): array
    {
        $ra = [];

        foreach ($this->dongLoi as $x) {
            $loai = isset($x['loai']) ? $x['loai'] : '';

            $ra[] = [
                isset($x['dong']) ? (int) $x['dong'] : 0,
                $loai === KetQuaNhapDanhMuc::LOAI_BO_QUA ? 'Bỏ qua' : 'Lỗi',
                isset($x['ly_do']) ? (string) $x['ly_do'] : '',
            ];
        }

        return $ra;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['A' => 12, 'B' => 12, 'C' => 110] as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                    $sheet->getStyle($cot . '1')->getFont()->setBold(true);
                    $sheet->getStyle($cot . '1')->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF2CC');
                }

                // Ly do thuong la thong diep loi rat dai -> cho xuong dong trong o.
                $cuoi = count($this->dongLoi) + 1;
                $sheet->getStyle('C2:C' . max(2, $cuoi))->getAlignment()->setWrapText(true);
            },
        ];
    }
}
