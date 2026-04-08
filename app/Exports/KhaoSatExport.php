<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use DB;

class KhaoSatExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithMapping
{
    protected $date_from;
    protected $date_to;
    protected $execute_room_id;
    protected $rowNumber = 0;
    protected $reportDataService;

    public function __construct($date_from, $date_to, $execute_room_id = null)
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->execute_room_id = $execute_room_id;
        $this->reportDataService = new ReportDataService();
    }

    public function collection()
    {
        $request = new Request([
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'execute_room_id' => $this->execute_room_id,
        ]);

        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsKhaoSat($request);

        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            [
                'STT',
                'Mã Điều Trị',
                'Họ Tên BN',
                'Năm Sinh',
                'Giới Tính',
                'TG Tiếp Đón',
                'TG Khám',
                'Phòng Khám',
                'Bác Sỹ',
                'TG Khám (phút)',
                'CLS',
                'TG Chờ (phút)',
                'Mã Bệnh',
                'Chẩn Đoán',
                'XN Huyết học', '',
                'XN Vi sinh', '',
                'XN Sinh hóa', '',
                'XN Miễn dịch', '',
                'XN Nước tiểu', '',
                'XN Khác', '',
                'CĐHA X-Quang', '',
                'CĐHA CT', '',
                'CĐHA MRI', '',
                'CĐHA Khác', '',
                'Siêu âm', '',
                'Nội soi', '',
                'TDCN', '',
                'Giải phẫu bệnh', '',
            ],
            [
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
                'CĐ', 'KQ',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge header row 1 cho cac cot co dinh
                foreach (range('A', 'N') as $i => $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // Merge header row 1 cho cac nhom CLS (moi nhom 2 cot)
                $clsStartCols = ['O','Q','S','U','W','Y','AA','AC','AE','AG','AI','AK','AM','AO'];
                $clsEndCols   = ['P','R','T','V','X','Z','AB','AD','AF','AH','AJ','AL','AN','AP'];
                for ($i = 0; $i < count($clsStartCols); $i++) {
                    $sheet->mergeCells("{$clsStartCols[$i]}1:{$clsEndCols[$i]}1");
                }

                // Style header
                $sheet->getStyle('A1:AP2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(8);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(16);
                $sheet->getColumnDimension('J')->setWidth(10);
                $sheet->getColumnDimension('K')->setWidth(5);
                $sheet->getColumnDimension('L')->setWidth(10);
                $sheet->getColumnDimension('M')->setWidth(10);
                $sheet->getColumnDimension('N')->setWidth(25);

                // CLS columns width (O -> AL)
                foreach (range('O', 'Z') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(16);
                }
                foreach (['AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(16);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    private function maskName($name)
    {
        if (empty($name)) return '';
        $parts = explode(' ', trim($name));
        if (count($parts) <= 1) return str_repeat('*', mb_strlen($name));
        $last = array_pop($parts);
        $maskedLast = mb_substr($last, 0, 1) . str_repeat('*', max(mb_strlen($last) - 1, 0));
        $parts[] = $maskedLast;
        return implode(' ', $parts);
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $thoiGianKham = '';
        if ($row->start_time && $row->finish_time) {
            $start = Carbon::createFromFormat('YmdHis', $row->start_time);
            $finish = Carbon::createFromFormat('YmdHis', $row->finish_time);
            $thoiGianKham = $start->diffInMinutes($finish);
        }

        $thoiGianCho = '';
        if ($row->tiep_don_time && $row->start_time) {
            $tiepdon = Carbon::createFromFormat('YmdHis', $row->tiep_don_time);
            $start = Carbon::createFromFormat('YmdHis', $row->start_time);
            $thoiGianCho = $tiepdon->diffInMinutes($start);
        }

        return [
            $this->rowNumber,
            $row->tdl_treatment_code,
            $this->maskName($row->tdl_patient_name),
            dob($row->tdl_patient_dob),
            $row->tdl_patient_gender_name,
            strtodatetime($row->tiep_don_time),
            strtodatetime($row->kham_time),
            $row->phong_kham,
            $row->bac_sy,
            $thoiGianKham,
            $row->co_cls,
            $thoiGianCho,
            $row->ma_benh,
            $row->chan_doan,
            strtodatetime($row->xn_hh_cd), strtodatetime($row->xn_hh_kq),
            strtodatetime($row->xn_vs_cd), strtodatetime($row->xn_vs_kq),
            strtodatetime($row->xn_sh_cd), strtodatetime($row->xn_sh_kq),
            strtodatetime($row->xn_md_cd), strtodatetime($row->xn_md_kq),
            strtodatetime($row->xn_nt_cd), strtodatetime($row->xn_nt_kq),
            strtodatetime($row->xn_khac_cd), strtodatetime($row->xn_khac_kq),
            strtodatetime($row->cdha_xq_cd), strtodatetime($row->cdha_xq_kq),
            strtodatetime($row->cdha_ct_cd), strtodatetime($row->cdha_ct_kq),
            strtodatetime($row->cdha_mri_cd), strtodatetime($row->cdha_mri_kq),
            strtodatetime($row->cdha_khac_cd), strtodatetime($row->cdha_khac_kq),
            strtodatetime($row->sa_cd), strtodatetime($row->sa_kq),
            strtodatetime($row->ns_cd), strtodatetime($row->ns_kq),
            strtodatetime($row->tdcn_cd), strtodatetime($row->tdcn_kq),
            strtodatetime($row->gpb_cd), strtodatetime($row->gpb_kq),
        ];
    }
}
