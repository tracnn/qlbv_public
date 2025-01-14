<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use DB;

class AccountantRevenueDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    public function __construct($request)
    {
        $this->request = $request;
        $this->reportDataService = new ReportDataService(); // Khởi tạo service
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsAccountantRevenue($this->request);

        // Sử dụng DB::select để thực thi câu lệnh SQL với bindings
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'Khoa chỉ định',
            'Phòng chỉ định',
            'Xét nghiệm',
            'CĐHA',
            'Thuốc',
            'Máu',
            'Thủ thuật',
            'VTYT',
            'Nội soi',
            'TDCN',
            'Siêu âm',
            'Phẫu thuật',
            'GPB',
            'Suất ăn',
            'Khác',
            'Khám',
            'Giường'
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(12);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(10);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(12);
                $sheet->getColumnDimension('L')->setWidth(12);
                $sheet->getColumnDimension('M')->setWidth(12);
                $sheet->getColumnDimension('N')->setWidth(12);
                $sheet->getColumnDimension('O')->setWidth(12);
                $sheet->getColumnDimension('P')->setWidth(12);
                $sheet->getColumnDimension('Q')->setWidth(12);

                $sheet->getStyle('C:Q')->getNumberFormat()
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
        return [
            $row->deptname,
            $row->roomname,
            $row->xn,
            $row->ha,
            $row->th,
            $row->ma,
            $row->tt,
            $row->vt,
            $row->ns,
            $row->cn,
            $row->sa,
            $row->pt,
            $row->gb,
            $row->an,
            $row->cl,
            $row->kh,
            $row->gi
        ];
    }
}
