<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Services\BHYT\BacSiYLenhQuery;

class BacSiYLenhExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $request;
    protected $rowNumber = 0;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        // File Excel lay ma khoa tren y lenh (q.ma_khoa), man danh sach lay khoa cua NVYT.
        $query = BacSiYLenhQuery::build(
            $this->request->input('date_type', 'date_yl'),
            $this->request->input('date_from'),
            $this->request->input('date_to'),
            'q.ma_khoa'
        );

        $results = $query->get();

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Khoa',
            'Tên Khoa',
            'Mã Bác sĩ',
            'Tên Bác sĩ',
            'Số lượng',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(12);

                // Format số cho cột số lượng
                $sheet->getStyle('F:F')->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);
        return [
            1 => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->ma_khoa,
            $row->ten_khoa,
            $row->ma_bac_si,
            $row->ho_ten,
            $row->tong_so,
        ];
    }
}

