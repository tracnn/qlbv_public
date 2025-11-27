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

class ThuocVtytTieuHaoDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
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

        $dateFrom  = $this->request->input('date_from');
        $dateTo    = $this->request->input('date_to');
        $date_type = $this->request->input('date_type', 'date_intruction');
        $department_catalog = $this->request->input('department_catalog');
        $patient_type = $this->request->input('patient_type');

        // Chuẩn hóa date_from, date_to
        if (strlen($dateFrom) == 10) { // YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)
                ->startOfDay()
                ->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)
                ->endOfDay()
                ->format('Y-m-d H:i:s');
        }

        // Đổi sang dạng YmdHis để so sánh với intruction_time
        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo   = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        // Chọn field ngày
        switch ($date_type) {
            case 'date_in':
                $dateField = 'tm.in_time';
                break;
            case 'date_out':
                $dateField = 'tm.out_time';
                break;
            case 'date_payment':
                $dateField = 'tm.fee_lock_time';
                break;
            case 'date_intruction':
            default:
                $dateField = 'sr.intruction_time';
                break;
        }

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_department as d', 'd.id', '=', 'sr.request_department_id')
            ->join('his_sere_serv as ss', 'ss.service_req_id', '=', 'sr.id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'ss.patient_type_id')
            ->join('his_service as s', 's.id', '=', 'ss.service_id')
            ->join('his_service_type as st', 'st.id', '=', 's.service_type_id')
            ->join('his_service_unit as su', 'su.id', '=', 's.service_unit_id')
            ->leftJoin('his_exp_mest_medicine as emm', 'emm.id', '=', 'ss.exp_mest_medicine_id')
            ->leftJoin('his_exp_mest_material as emt', 'emt.id', '=', 'ss.exp_mest_material_id')
            ->leftJoin('his_treatment as tm', 'tm.id', '=', 'sr.treatment_id')
            ->select([
                'd.department_name',
                'pt.patient_type_name',
                'st.service_type_name',
                's.service_name',
                'su.service_unit_name',
                DB::raw('NVL(emm.is_export, emt.is_export) as is_export'),
                DB::raw('SUM(ss.amount) as total_sere_serv_amount'),
                DB::raw('NVL(SUM(emm.amount), SUM(emt.amount)) as total_export_amount'),
                DB::raw('NVL(SUM(emm.pres_amount), SUM(emt.pres_amount)) as total_pres_amount'),
                DB::raw('NVL(SUM(emm.th_amount), SUM(emt.th_amount)) as total_th_amount'),
            ])
            ->where('ss.is_expend', 1)
            ->whereIn('s.service_type_id', [6, 7])
            ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

        if (!empty($department_catalog)) {
            $query->where('d.id', $department_catalog);
        }
        if (!empty($patient_type)) {
            $query->where('pt.id', $patient_type);
        }

        $query->groupBy(
            'd.department_name',
            'pt.patient_type_name',
            'st.service_type_name',
            's.service_name',
            'su.service_unit_name',
            DB::raw('NVL(emm.is_export, emt.is_export)')
        );

        $results = $query->get();

        return new Collection($results);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Khoa',
            'Đối tượng',
            'Loại dịch vụ',
            'Tên dịch vụ',
            'Đơn vị tính',
            'Trạng thái xuất',
            'Số lượng xuất',
            'Số lượng y lệnh',
            'Số lượng kê đơn',
            'Số lượng tiêu hao'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(15);

                // Format số cho các cột số lượng
                $sheet->getStyle('H:K')->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
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
            $row->department_name,
            $row->patient_type_name,
            $row->service_type_name,
            $row->service_name,
            $row->service_unit_name,
            $row->is_export == 1 ? 'Đã xuất' : 'Chưa xuất',
            number_format($row->total_export_amount, 2),
            number_format($row->total_sere_serv_amount, 2),
            number_format($row->total_pres_amount, 2),
            number_format($row->total_th_amount, 2),
        ];
    }
}

