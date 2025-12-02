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
use Illuminate\Support\Collection;
use DB;
use Carbon\Carbon;

class BacSiYLenhExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $request;
    protected $rowNumber = 0;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        $date_type = $this->request->input('date_type', 'date_yl');
        $dateFrom  = $this->request->input('date_from');
        $dateTo    = $this->request->input('date_to');

        // Nếu ngày ở dạng YYYY-MM-DD thì chuẩn hoá về Y-m-d H:i:s
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)
                ->startOfDay()
                ->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)
                ->endOfDay()
                ->format('Y-m-d H:i:s');
        }

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' cho các field kiểu chuỗi YmdHi
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
        $formattedDateToForFields   = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

        // Timestamp giữ nguyên dạng Y-m-d H:i:s
        $formattedDateFromForTimestamp = $dateFrom;
        $formattedDateToForTimestamp   = $dateTo;

        // Chọn field ngày và format tương ứng theo date_type
        switch ($date_type) {
            case 'date_in':
                $dateField         = 'ngay_vao';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField         = 'ngay_ra';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField         = 'ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField         = 'created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo   = $formattedDateToForTimestamp;
                break;
            case 'date_update':
                $dateField         = 'updated_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo   = $formattedDateToForTimestamp;
                break;
            default:
                // mặc định lọc theo ngày y lệnh
                $dateField         = 'ngay_yl';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
        }

        // ---------- SUBQUERY 1: XML2 ----------
        $subQueryXml2 = DB::table('qd130_xml2s as q')
            ->leftJoin('medical_staffs as ms', 'ms.macchn', '=', 'q.ma_bac_si')
            ->whereBetween("q.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->select([
                'q.ma_khoa',
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'q.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
            ]);

        // ---------- SUBQUERY 2: XML3 + UNION ALL ----------
        $subQueryUnion = DB::table('qd130_xml3s as q')
            ->leftJoin('medical_staffs as ms', 'ms.macchn', '=', 'q.ma_bac_si')
            ->whereBetween("q.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->select([
                'q.ma_khoa',
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'q.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
            ])
            ->unionAll($subQueryXml2);

        // ---------- WRAP SUBQUERY LẠI RỒI GROUP BY ----------
        $query = DB::table(DB::raw("({$subQueryUnion->toSql()}) as t"))
            ->mergeBindings($subQueryUnion)
            ->select([
                't.ma_khoa',
                't.ten_khoa',
                't.ma_bac_si',
                't.ho_ten',
                DB::raw('COUNT(*) as tong_so'),
            ])
            ->groupBy(
                't.ma_khoa',
                't.ten_khoa',
                't.ma_bac_si',
                't.ho_ten'
            );

        $results = $query->get();

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Khoa',
            'Tên Khoa',
            'Mã Bác sĩ',
            'Tên Bác sĩ',
            'Số lượng',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(12);

                // Format số cho cột số lượng
                $sheet->getStyle('F:F')->getNumberFormat()
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
            $row->ma_khoa,
            $row->ten_khoa,
            $row->ma_bac_si,
            $row->ho_ten,
            $row->tong_so,
        ];
    }
}

