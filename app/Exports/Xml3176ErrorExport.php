<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Xml3176XmlErrorResult;
use App\Models\BHYT\Xml3176XmlErrorCatalog;
use App\Models\BHYT\Xml3176Xml1;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class Qd130ErrorExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping, WithTitle
{
    protected $fromDate;
    protected $toDate;
    protected $xml_filter_status;
    protected $date_type;
    protected $xml3176_xml_error_catalog_id;
    protected $payment_date_filter;
    protected $rowNumber = 0;
    protected $imported_by;
    protected $xml_submit_status;

    public function __construct($fromDate = null, $toDate = null, $xml_filter_status = null, 
        $date_type, $xml3176_xml_error_catalog_id = null, $payment_date_filter = null, $imported_by = null, $xml_submit_status = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->xml_filter_status = $xml_filter_status;
        $this->date_type = $date_type;
        $this->xml3176_xml_error_catalog_id = $xml3176_xml_error_catalog_id;
        $this->payment_date_filter = $payment_date_filter;
        $this->imported_by = $imported_by;
        $this->xml_submit_status = $xml_submit_status;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;
        $xml_filter_status = $this->xml_filter_status;
        $date_type = $this->date_type;
        $xml3176_xml_error_catalog_id = $this->xml3176_xml_error_catalog_id;
        $payment_date_filter = $this->payment_date_filter;
        $imported_by = $this->imported_by;
        $xml_submit_status = $this->xml_submit_status;

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
        $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

        // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'xml3176_xml1s.ngay_vao';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField = 'xml3176_xml1s.ngay_ra';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField = 'xml3176_xml1s.ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField = 'xml3176_xml1s.created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            case 'date_update':
                $dateField = 'xml3176_xml1s.updated_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            default:
                $dateField = 'xml3176_xml1s.ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
        }

        $query = Xml3176Xml1::whereBetween($dateField, [$formattedDateFrom, $formattedDateTo])
            ->join('xml3176_xml_error_results', 'xml3176_xml_error_results.ma_lk', '=', 'xml3176_xml1s.ma_lk')
            ->join('xml3176_xml_error_catalogs', 'xml3176_xml_error_results.error_code', '=', 'xml3176_xml_error_catalogs.error_code')
            ->join('xml3176_xml_informations', 'xml3176_xml_informations.ma_lk', '=', 'xml3176_xml1s.ma_lk')
            ->select('xml3176_xml_error_results.*', 'xml3176_xml_error_catalogs.error_name as catalog_error_name',
                'xml3176_xml1s.ngay_vao', 'xml3176_xml1s.ngay_ra', 'xml3176_xml1s.ma_bn', 'xml3176_xml1s.ho_ten',
                'xml3176_xml1s.ngay_sinh', 'xml3176_xml1s.ma_the_bhyt', 'xml3176_xml1s.ngay_ttoan', 
                'xml3176_xml_informations.imported_by' , 'xml3176_xml_informations.exported_by')
            ->orderBy('xml3176_xml_error_results.ma_lk')
            ->orderBy('xml3176_xml_error_results.xml')
            ->orderBy('xml3176_xml_error_results.stt');

        if ($xml_filter_status === 'has_error_critical') {
            $query->where('xml3176_xml_error_results.critical_error', true);
        } elseif ($xml_filter_status === 'has_error_warning') {
            $query->where('xml3176_xml_error_results.critical_error', false);
        }

        // Apply filter based on xml_submit_status
        if ($xml_submit_status === 'has_submit') {
            $query->whereNotNull('xml3176_xml_informations.submitted_at');
        } elseif ($xml_submit_status === 'not_submit') {
            $query->whereNull('xml3176_xml_informations.submitted_at');
        }

        if (!empty($xml3176_xml_error_catalog_id)) {
            $query->where('xml3176_xml_error_catalogs.id', $xml3176_xml_error_catalog_id);
        }

        if ($payment_date_filter === 'has_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '!=', '');
        } elseif ($payment_date_filter === 'no_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '=', '');
        }

        // Apply filter based on imported_by
        if (!empty($imported_by)) {
            $query = $query->whereHas('Xml3176XmlInformation', function ($query) use ($imported_by) {
                $query->where('imported_by', $imported_by);
            });
        } else {
            // Kiểm tra role của user
            if (!\Auth::user()->hasRole(['superadministrator', 'administrator'])) {
                // Nếu không có vai trò superadministrator hoặc administrator thì lọc theo người import
                $query = $query->whereHas('Xml3176XmlInformation', function($query) {
                    $query->where('imported_by', \Auth::user()->loginname); // Lọc theo loginname của user hiện tại
                });
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại XML',
            'STT XML',
            'Mã Liên Kết',
            'Mã Bệnh Nhân',
            'Họ Và Tên',
            'Ngày Sinh',
            'Mã Thẻ BHYT',
            'Ngày Vào',
            'Ngày Ra',
            'Ngày T.Toán',
            'Ngày Y Lệnh',
            'Ngày Kết Quả',
            'Mã Lỗi',
            'Mô Tả',
            'Loại lỗi',
            'Imported by',
            'Exported by'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(10);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(13);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(13);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(13);
                $sheet->getColumnDimension('J')->setWidth(13);
                $sheet->getColumnDimension('K')->setWidth(13);
                $sheet->getColumnDimension('l')->setWidth(13);
                $sheet->getColumnDimension('M')->setWidth(13);
                $sheet->getColumnDimension('N')->setWidth(30);
                $sheet->getColumnDimension('O')->setWidth(50);
                $sheet->getColumnDimension('P')->setWidth(13);
                $sheet->getColumnDimension('Q')->setWidth(12);
                $sheet->getColumnDimension('R')->setWidth(12);

                $sheet->getStyle('G')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('J')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('K')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('L')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('M')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
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
            $data->stt,
            $data->ma_lk,
            $data->ma_bn,
            $data->ho_ten,
            $data->ngay_sinh,
            $data->ma_the_bhyt,
            $data->ngay_vao,
            $data->ngay_ra,
            $data->ngay_ttoan,
            $data->ngay_yl,
            $data->ngay_kq,
            $data->catalog_error_name,
            $data->description,
            $data->critical_error ? 'Nghiêm trọng' : 'Cảnh báo',
            $data->imported_by,
            $data->exported_by,
        ];
    }

    public function title(): string
    {
        return 'Lỗi XML'; // Tên cho sheet này
    }
}
