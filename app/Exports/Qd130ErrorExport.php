<?php

namespace App\Exports;

use App\Models\BHYT\Qd130XmlErrorResult;
use App\Models\BHYT\Qd130XmlErrorCatalog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class Qd130ErrorExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $fromDate;
    protected $toDate;

    protected $rowNumber = 0;

    public function __construct($fromDate = null, $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;

        return Qd130XmlErrorResult::whereBetween('qd130_xml_error_results.updated_at', [$dateFrom, $dateTo])
        ->join('qd130_xml_error_catalogs', 'qd130_xml_error_results.error_code', '=', 'qd130_xml_error_catalogs.error_code')
        ->select('qd130_xml_error_results.*', 'qd130_xml_error_catalogs.error_name as catalog_error_name')
        ->orderBy('qd130_xml_error_results.xml','qd130_xml_error_results.ma_lk','qd130_xml_error_results.ngay_yl');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại XML',
            'Mã Liên Kết',
            'STT XML',
            'Ngày Y Lệnh',
            'Ngày Kết Quả',
            'Mã Lỗi',
            'Mô Tả',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(10);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(8);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(50);

                // $sheet->getStyle('D')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                // $sheet->getStyle('O')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                // $sheet->getStyle('P')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                // $sheet->getStyle('V')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);
        return [
            // Căn giữa tiêu đề
            1    => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ],
                'font' => [
                    'bold' => true, // In đậm tiêu đề
                ],
            ],
        ];
    }

    public function map($data): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $data->xml,
            $data->ma_lk,
            $data->stt,
            $data->ngay_yl ? Carbon::parse($data->ngay_yl)->format('d/m/Y H:i') : null,
            $data->ngay_kq ? Carbon::parse($data->ngay_kq)->format('d/m/Y H:i') : null,
            $data->catalog_error_name,
            $data->description,
        ];
    }
}