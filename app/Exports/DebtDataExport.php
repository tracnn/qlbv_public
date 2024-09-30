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

class DebtDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $rowNumber = 0;
    protected $reportDataService;
    protected $request;

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
        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsDebt($this->request);

        // Sử dụng DB::select để thực thi câu lệnh SQL với bindings
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        return new Collection($results);
    }
    public function headings(): array
    {
        return [
            'STT',
            'Mã điều Trị',
            'Họ và tên',
            'Ngày sinh',
            'Địa chỉ',
            'Ngày vào',
            'Ngày ra',
            'Số điện thoại',
            'Đối tượng',
            'Khoa điều trị',
            'Tổng chi phí',
            'BH t.toán',
            'BN t.toán',
            'Đã t.toán',
            'Tạm ứng',
            'Hoàn ứng',
            'Chi phí khác',
            'Còn lại'
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(12);
                $sheet->getColumnDimension('I')->setWidth(10);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(12);
                $sheet->getColumnDimension('L')->setWidth(12);
                $sheet->getColumnDimension('M')->setWidth(12);
                $sheet->getColumnDimension('N')->setWidth(12);
                $sheet->getColumnDimension('O')->setWidth(12);
                $sheet->getColumnDimension('P')->setWidth(12);
                $sheet->getColumnDimension('Q')->setWidth(12);
                $sheet->getColumnDimension('R')->setWidth(12);

                $sheet->getStyle('K:R')->getNumberFormat()
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
            $row->treatment_code,
            $row->tdl_patient_name,
            strtodate($row->tdl_patient_dob),
            $row->tdl_patient_address,
            strtodatetime($row->in_time),
            strtodatetime($row->out_time),
            $row->tdl_patient_phone,
            $row->patient_type_name,
            $row->department_name,
            ($row->total_price),
            ($row->total_hein_price),
            ($row->total_patient_price),
            ($row->da_thanh_toan),
            ($row->tam_ung),
            ($row->hoan_ung),
            ($row->tu_nhap),
            ($row->can_thanh_toan)
        ];
    }
}
