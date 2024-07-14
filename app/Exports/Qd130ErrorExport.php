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
    protected $xml_filter_status;

    protected $rowNumber = 0;

    public function __construct($fromDate = null, $toDate = null, $xml_filter_status = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->xml_filter_status = $xml_filter_status;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;
        $xml_filter_status = $this->xml_filter_status;

        $query = Qd130XmlErrorResult::whereBetween('qd130_xml_error_results.updated_at', [$dateFrom, $dateTo])
            ->join('qd130_xml_error_catalogs', 'qd130_xml_error_results.error_code', '=', 'qd130_xml_error_catalogs.error_code')
            ->select('qd130_xml_error_results.*', 'qd130_xml_error_catalogs.error_name as catalog_error_name')
            ->orderBy('qd130_xml_error_results.ma_lk');

        if ($xml_filter_status === 'has_error_critical') {
            $query->where('qd130_xml_error_results.critical_error', true);
        } elseif ($xml_filter_status === 'has_error_warning') {
            $query->where('qd130_xml_error_results.critical_error', false);
        }

        return $query;
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
            'Loại lỗi',
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
                $sheet->getColumnDimension('G')->setWidth(35);
                $sheet->getColumnDimension('H')->setWidth(60);
                $sheet->getColumnDimension('I')->setWidth(15);

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
            $data->critical_error ? 'Nghiêm trọng' : 'Cảnh báo',
        ];
    }
}
