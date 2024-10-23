<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use App\Models\CheckBHYT\check_hein_card;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class HeinCardErrorExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping, WithTitle
{
    protected $fromDate;
    protected $toDate;
    protected $rowNumber = 0;

    public function __construct($fromDate = null, $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;

        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        return check_hein_card::where(function($query) {
            $query->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
            ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
        })
        ->whereBetween('updated_at', [$formattedDateFromForTimestamp, $formattedDateToForTimestamp]);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã điều trị',
            'Mã kiểm tra',
            'Mã kết quả',
            'Ghi chú',
            'Mã thẻ',
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(13);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(50);
                $sheet->getColumnDimension('F')->setWidth(18);
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
            $data->ma_lk,
            config('__tech.check_insurance_code')[$data->ma_kiemtra],
            config('__tech.insurance_error_code')[$data->ma_ketqua],
            $data->ghi_chu,
            $data->ma_the,
        ];
    }

    public function title(): string
    {
        return 'Lỗi thẻ BHYT'; // Tên cho sheet này
    }
}