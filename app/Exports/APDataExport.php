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
use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use DB;

class APDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $date_from;
    protected $date_to;
    protected $rowNumber = 0;
    protected $reportDataService;

    public function __construct($date_from, $date_to)
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->reportDataService = new ReportDataService(); // Khởi tạo service
    }

    public function collection()
    {
        $request = new Request([
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);

        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsPa($request);

        // Sử dụng DB::select để thực thi câu lệnh SQL với bindings
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Điều Trị',
            'Mã Bệnh Nhân',
            'Tên Bệnh Nhân',
            'Ngày Sinh',
            'Mã Giao Dịch',
            'Ngày Giao Dịch',
            'Số Tiền',
            'Kế Toán',
            'Loại Thanh Toán',
            'Hình Thức Thanh Toán',
            'Khoa Điều Trị'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(25);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(20);
                $sheet->getColumnDimension('L')->setWidth(25);

                $sheet->getStyle('H')->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('E')->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('G')->getNumberFormat()
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
        // Chuyển đổi định dạng ngày tháng
        $formattedDob = \Carbon\Carbon::createFromFormat('YmdHis', $row->tdl_patient_dob)->format('d/m/Y');
        $formattedTransactionTime = \Carbon\Carbon::createFromFormat('YmdHis', $row->transaction_time)->format('d/m/Y H:i');

        return [
            $this->rowNumber,
            $row->tdl_treatment_code,
            $row->tdl_patient_code,
            $row->tdl_patient_name,
            $row->tdl_patient_dob,
            $row->transaction_code,
            $row->transaction_time,
            $row->amount,
            $row->cashier_username,
            $row->transaction_type_name,
            $row->pay_form_name,
            $row->department_name
        ];
    }
}
