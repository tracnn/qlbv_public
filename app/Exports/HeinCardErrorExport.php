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
    /**
     * Truy van con tra ve tap ma_lk duoc phep xuat, hoac null nghia la khong cat.
     *
     * Lop nay dung chung cho CA hai man QD130 va XML3176. Man QD130 goi khong kem tham
     * so nay nen giu nguyen hanh vi cu; rieng man XML3176 truyen vao tap ho so ma nguoi
     * dung dang nhin thay, vi truoc day sheet nay chi nhan khoang ngay va bo QUA moi bo
     * loc khac - ke ca ma co so, nen file xuat tron ca co so khac.
     *
     * Bang check_hein_card khong co cot ma_cskcb, nen cat theo ma_lk la cach duy nhat.
     */
    protected $maLkChoPhep;
    /** Khoa config chua danh sach ma kiem tra / ma ket qua duoc coi la loi. */
    protected $khoaCauHinh;

    public function __construct($fromDate = null, $toDate = null, $maLkChoPhep = null,
        $khoaCauHinh = 'qd130xml')
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->maLkChoPhep = $maLkChoPhep;
        $this->khoaCauHinh = $khoaCauHinh;
    }

    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;

        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        $khoa = $this->khoaCauHinh;

        $query = check_hein_card::where(function($query) use ($khoa) {
            $query->whereIn('ma_kiemtra', config($khoa . '.hein_card_invalid.check_code', []))
            ->orWhereIn('ma_tracuu', config($khoa . '.hein_card_invalid.result_code', []));
        })
        ->whereBetween('updated_at', [$formattedDateFromForTimestamp, $formattedDateToForTimestamp]);

        if ($this->maLkChoPhep !== null) {
            $query->whereIn('ma_lk', $this->maLkChoPhep);
        }

        return $query;
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
            \App\Services\BHYT\NhanMaThe::kiemTra($data->ma_kiemtra),
            \App\Services\BHYT\NhanMaThe::traCuu($data->ma_ketqua),
            $data->ghi_chu,
            $data->ma_the,
        ];
    }

    public function title(): string
    {
        return 'Lỗi thẻ BHYT'; // Tên cho sheet này
    }
}