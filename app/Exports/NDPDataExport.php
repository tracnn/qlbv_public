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

class NDPDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $drug_req_type;
    protected $prescription_type;
    protected $treatment_code;
    protected $date_from;
    protected $date_to;
    protected $rowNumber = 0;
    protected $reportDataService;

    public function __construct($drug_req_type, $prescription_type, $treatment_code, $date_from, $date_to)
    {
        $this->drug_req_type = $drug_req_type;
        $this->prescription_type = $prescription_type;
        $this->treatment_code = $treatment_code;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->reportDataService = new ReportDataService(); // Khởi tạo service
    }

    public function collection()
    {
        $request = new Request([
            'drug_req_type' => $this->drug_req_type,
            'prescription_type' => $this->prescription_type,
            'treatment_code' => $this->treatment_code,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);

        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsNdp($request);

        // Sử dụng DB::select để thực thi câu lệnh SQL với bindings
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Điều Trị',
            'Mã Y Lệnh',
            'Mã Bệnh Nhân',
            'Tên Bệnh Nhân',
            'Phòng Yêu Cầu',
            'BS Chỉ Định',
            'Mã ICD',
            'Mã Phụ ICD',
            'Tên ICD',
            'Mô Tả ICD',
            'Loại Điều Trị',
            'Loại Đơn Thuốc',
            'Trạng Thái',
            'Thời Gian Y Lệnh',
            'Kiểu Kê Đơn',
            'Số Lượng Thuốc',
            'Số Lượng Mua Ngoài'
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
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(20);
                $sheet->getColumnDimension('L')->setWidth(25);
                $sheet->getColumnDimension('M')->setWidth(25);
                $sheet->getColumnDimension('N')->setWidth(20);
                $sheet->getColumnDimension('O')->setWidth(20);
                $sheet->getColumnDimension('P')->setWidth(20);

                $sheet->getStyle('O')->getNumberFormat()
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
            $row->tdl_treatment_code,
            $row->service_req_code,
            $row->tdl_patient_code,
            $row->tdl_patient_name,
            $row->request_room_name,
            $row->request_username,
            $row->icd_code,
            $row->icd_sub_code,
            $row->icd_name,
            $row->icd_text,
            $row->treatment_type_name,
            $row->service_req_type_name,
            $row->service_req_stt_name,
            $row->intruction_time,
            $this->formatPrescriptionType($row->prescription_type_id),
            $row->drug_count,
            $row->mety_count
        ];
    }

    private function formatPrescriptionType($prescriptionTypeId)
    {
        switch ($prescriptionTypeId) {
            case 1:
                return "Đơn tân dược";
            case 2:
                return "Đơn YHCT";
            case 3:
                return "Đơn CLS";
            default:
                return $prescriptionTypeId;
        }
    }
}
