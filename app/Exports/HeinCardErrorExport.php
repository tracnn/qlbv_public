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
    /**
     * Them cot Ma Khoa (khoa ho so XML3176) sau cot Ma dieu tri.
     *
     * Mac dinh TAT: man QD130 dung chung lop nay va file cua man do phai giu nguyen.
     */
    protected $coMaKhoa;

    public function __construct($fromDate = null, $toDate = null, $maLkChoPhep = null,
        $khoaCauHinh = 'qd130xml', $coMaKhoa = false)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->maLkChoPhep = $maLkChoPhep;
        $this->khoaCauHinh = $khoaCauHinh;
        $this->coMaKhoa = (bool) $coMaKhoa;
    }

    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;

        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        $khoa = $this->khoaCauHinh;

        if (!$this->coMaKhoa) {
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

        // Co join xml3176_xml1s: MOI cot cua check_hein_card phai ghi ro bang, vi hai bang
        // cung co ma_lk, updated_at.
        $bang = (new check_hein_card)->getTable();

        $query = check_hein_card::query()
            ->leftJoin('xml3176_xml1s', 'xml3176_xml1s.ma_lk', '=', $bang . '.ma_lk')
            ->select($bang . '.*')
            ->selectRaw('xml3176_xml1s.ma_khoa as ma_khoa_xuat')
            ->where(function($query) use ($khoa, $bang) {
                $query->whereIn($bang . '.ma_kiemtra', config($khoa . '.hein_card_invalid.check_code', []))
                ->orWhereIn($bang . '.ma_tracuu', config($khoa . '.hein_card_invalid.result_code', []));
            })
            ->whereBetween($bang . '.updated_at', [$formattedDateFromForTimestamp, $formattedDateToForTimestamp]);

        if ($this->maLkChoPhep !== null) {
            $query->whereIn($bang . '.ma_lk', $this->maLkChoPhep);
        }

        return $query;
    }

    public function headings(): array
    {
        $h = [
            'STT',
            'Mã điều trị',
            'Mã kiểm tra',
            'Mã kết quả',
            'Ghi chú',
            'Mã thẻ',
        ];

        if ($this->coMaKhoa) {
            array_splice($h, 2, 0, ['Mã Khoa']);
        }

        return $h;
    }

    public function registerEvents(): array
    {
        $doRong = $this->coMaKhoa
            ? ['A' => 5, 'B' => 13, 'C' => 10, 'D' => 15, 'E' => 15, 'F' => 50, 'G' => 18]
            : ['A' => 5, 'B' => 13, 'C' => 15, 'D' => 15, 'E' => 50, 'F' => 18];

        return [
            AfterSheet::class => function(AfterSheet $event) use ($doRong) {
                $sheet = $event->sheet->getDelegate();
                foreach ($doRong as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                }
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

        $dong = [
            $this->rowNumber,
            $data->ma_lk,
            \App\Services\BHYT\NhanMaThe::kiemTra($data->ma_kiemtra),
            \App\Services\BHYT\NhanMaThe::traCuu($data->ma_ketqua),
            $data->ghi_chu,
            $data->ma_the,
        ];

        if ($this->coMaKhoa) {
            array_splice($dong, 2, 0, [$data->ma_khoa_xuat]);
        }

        return $dong;
    }

    public function title(): string
    {
        return 'Lỗi thẻ BHYT'; // Tên cho sheet này
    }
}