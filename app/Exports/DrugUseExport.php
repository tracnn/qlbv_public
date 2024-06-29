<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use DB;

class DrugUseExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
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

        return DB::connection('HISPro')
            ->table('his_sere_serv as ss')
            ->join('his_service_req as sr', 'sr.id', '=', 'ss.service_req_id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'sr.tdl_patient_type_id')
            ->join('his_service_type as st', 'st.id', '=', 'ss.tdl_service_type_id')
            ->join('his_treatment as tm', 'tm.id', '=', 'sr.treatment_id')
            ->join('his_treatment_type as tt', 'tt.id', '=', 'sr.treatment_type_id')
            ->leftJoin('his_department as re_dept', 're_dept.id', '=', 'ss.tdl_request_department_id')
            ->leftJoin('his_medicine as mc', 'mc.id', '=', 'ss.medicine_id')
            ->join('his_medicine_type as mt', 'mt.id', '=', 'mc.medicine_type_id')
            ->join('his_medicine_use_form as muf', 'muf.id', '=', 'mt.medicine_use_form_id')
            ->select(
                'sr.tdl_patient_name',
                'sr.tdl_patient_gender_name',
                'sr.tdl_patient_dob',
                'sr.tdl_treatment_code',
                'sr.tdl_patient_code',
                'sr.service_req_code',
                'sr.request_username',
                'tm.tdl_hein_card_number',
                'pt.patient_type_name',
                'sr.icd_code',
                'sr.icd_name',
                'sr.icd_sub_code',
                'sr.icd_text',
                'tm.in_time',
                'tm.out_time',
                'tt.treatment_type_name',
                'st.service_type_name',
                'ss.amount',
                'ss.original_price',
                're_dept.department_name',
                'ss.tdl_intruction_time',
                'ss.tdl_hein_service_bhyt_name',
                'mt.medicine_type_code',
                'mt.medicine_type_name',
                'mt.concentra',
                'mt.active_ingr_bhyt_code',
                'mt.active_ingr_bhyt_name',
                'muf.medicine_use_form_name'
            )
            ->where('ss.is_delete', 0)
            ->whereNull('ss.is_expend')
            ->where('ss.tdl_service_type_id', 6)
            ->whereBetween('sr.intruction_time', [$dateFrom, $dateTo])
            ->orderBy('sr.intruction_time', 'asc'); // Thêm mệnh đề orderBy
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên Bệnh Nhân',
            'Giới Tính',
            'Ngày Sinh',
            'Mã Điều Trị',
            'Mã Bệnh Nhân',
            'Mã Y Lệnh',
            'Tên Người Yêu Cầu',
            'Số Thẻ BHYT',
            'Loại Bệnh Nhân',
            'Mã ICD',
            'Tên ICD',
            'Mã Phụ ICD',
            'Mô Tả ICD',
            'Thời Gian Vào',
            'Thời Gian Ra',
            'Loại Điều Trị',
            'Loại Dịch Vụ',
            'Số Lượng',
            'Giá Gốc',
            'Tên Khoa',
            'Ngày Y Lệnh',
            'Tên Dịch Vụ BHYT',
            'Mã Loại Thuốc',
            'Tên Loại Thuốc',
            'Nồng Độ',
            'Mã Hoạt Chất BHYT',
            'Tên Hoạt Chất BHYT',
            'Dạng Sử Dụng Thuốc'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(10);
                $sheet->getColumnDimension('D')->setWidth(10);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(20);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(10);
                $sheet->getColumnDimension('L')->setWidth(20);
                $sheet->getColumnDimension('M')->setWidth(15);
                $sheet->getColumnDimension('N')->setWidth(30);
                $sheet->getColumnDimension('O')->setWidth(15);
                $sheet->getColumnDimension('P')->setWidth(15);
                $sheet->getColumnDimension('Q')->setWidth(20);
                $sheet->getColumnDimension('R')->setWidth(15);
                $sheet->getColumnDimension('S')->setWidth(15);
                $sheet->getColumnDimension('T')->setWidth(20);
                $sheet->getColumnDimension('U')->setWidth(20);
                $sheet->getColumnDimension('V')->setWidth(15);
                $sheet->getColumnDimension('W')->setWidth(20);
                $sheet->getColumnDimension('X')->setWidth(15);
                $sheet->getColumnDimension('Y')->setWidth(20);
                $sheet->getColumnDimension('Z')->setWidth(20);
                $sheet->getColumnDimension('AA')->setWidth(20);
                $sheet->getColumnDimension('AB')->setWidth(20);
                $sheet->getColumnDimension('AC')->setWidth(20);

                $sheet->getStyle('D')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('O')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('P')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('V')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
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

    public function map($drugUse): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $drugUse->tdl_patient_name,
            $drugUse->tdl_patient_gender_name,
            $drugUse->tdl_patient_dob ? Carbon::parse($drugUse->tdl_patient_dob)->format('Ymd') : null,
            $drugUse->tdl_treatment_code,
            $drugUse->tdl_patient_code,
            $drugUse->service_req_code,
            $drugUse->request_username,
            $drugUse->tdl_hein_card_number,
            $drugUse->patient_type_name,
            $drugUse->icd_code,
            $drugUse->icd_name,
            $drugUse->icd_sub_code,
            $drugUse->icd_text,
            $drugUse->in_time,
            $drugUse->out_time,
            $drugUse->treatment_type_name,
            $drugUse->service_type_name,
            $drugUse->amount,
            $drugUse->original_price,
            $drugUse->department_name,
            $drugUse->tdl_intruction_time,
            $drugUse->tdl_hein_service_bhyt_name,
            $drugUse->medicine_type_code,
            $drugUse->medicine_type_name,
            $drugUse->concentra,
            $drugUse->active_ingr_bhyt_code,
            $drugUse->active_ingr_bhyt_name,
            $drugUse->medicine_use_form_name,
        ];
    }
}