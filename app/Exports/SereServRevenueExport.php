<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use DB;
use Carbon\Carbon;

class SereServRevenueExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $request;
    protected $reportDataService;
    protected $patientTypes;
    protected $treatmentTypes;

    public function __construct($request)
    {
        $this->request = $request;
        $this->reportDataService = new ReportDataService();
        $this->patientTypes = $this->reportDataService->getPatientTypes();
        $this->treatmentTypes = $this->reportDataService->getTreatmentTypes();
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        set_time_limit(1800);
        ini_set('memory_limit', '4096M');

        $dateFrom = $this->request->input('date_from');
        $dateTo = $this->request->input('date_to');
        $departmentId = $this->request->input('department_id');

        if (strlen($dateFrom) == 10) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }
        if (strlen($dateTo) == 10) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        $sql = $this->reportDataService->buildSereServRevenuePivotQuery($this->patientTypes, $this->treatmentTypes, $dateFrom, $dateTo, $departmentId);
        $results = DB::connection('HISPro')->select($sql);

        return new Collection($results);
    }

    public function headings(): array
    {
        // Heading row 3 (lowest level)
        $headings = ['Khoa', 'Loại dịch vụ'];
        foreach ($this->patientTypes as $pt) {
            foreach ($this->treatmentTypes as $tt) {
                $headings[] = 'SL';
                $headings[] = 'Thành tiền';
                $headings[] = 'Miễn giảm';
            }
        }
        return $headings;
    }

    public function map($row): array
    {
        $data = [$row->department_name, $row->service_type_name];
        foreach ($this->patientTypes as $pt) {
            foreach ($this->treatmentTypes as $tt) {
                $suffix = "_{$pt->id}_{$tt->id}";
                $slKey = "sl{$suffix}";
                $ttKey = "tt{$suffix}";
                $mgKey = "mg{$suffix}";
                $data[] = $row->{$slKey} == 0 ? '' : $row->{$slKey};
                $data[] = $row->{$ttKey} == 0 ? '' : $row->{$ttKey};
                $data[] = $row->{$mgKey} == 0 ? '' : $row->{$mgKey};
            }
        }
        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Start adding headings at row 1
                // Current headings() are at row 1 by default, but we want 3 rows.
                // We'll move the FromCollection results down by 2 rows.
                
                // Actually, WithHeadings by default puts them in row 1.
                // If we want 3 rows, we could do something like:
                
                $patientTypes = $this->patientTypes;
                $treatmentTypes = $this->treatmentTypes;
                $ttCount = count($treatmentTypes);
                
                // Level 1: Patient Types
                $sheet->insertNewRowBefore(1, 2);
                
                $colIndex = 3; // Start from column C
                foreach ($patientTypes as $pt) {
                    $sheet->setCellValueByColumnAndRow($colIndex, 1, $pt->patient_type_name);
                    $endColIndex = $colIndex + ($ttCount * 3) - 1;
                    $sheet->mergeCellsByColumnAndRow($colIndex, 1, $endColIndex, 1);
                    $colIndex = $endColIndex + 1;
                }
                
                // Level 2: Treatment Types
                $colIndex = 3;
                foreach ($patientTypes as $pt) {
                    foreach ($treatmentTypes as $tt) {
                        $sheet->setCellValueByColumnAndRow($colIndex, 2, $tt->treatment_type_name);
                        $sheet->mergeCellsByColumnAndRow($colIndex, 2, $colIndex + 2, 2);
                        $colIndex += 3;
                    }
                }
                
                // Merge 'Khoa' and 'Loại dịch vụ' across 3 rows
                $sheet->mergeCells('A1:A3');
                $sheet->setCellValue('A1', 'Khoa');
                $sheet->mergeCells('B1:B3');
                $sheet->setCellValue('B1', 'Loại dịch vụ');

                // Styling
                $sheet->getStyle('A1:B3')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle('1:3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('1:3')->getFont()->setBold(true);

                // Formatting numbers
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex - 1);
                $highestRow = $sheet->getHighestRow();
                
                // Set column width and formatting for data columns
                // Mỗi nhóm 3 cột: SL (offset 0), Thành tiền (offset 1), Miễn giảm (offset 2)
                for ($col = 3; $col < $colIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($columnLetter)->setWidth(14);
                    $sheet->getStyle($columnLetter . '4:' . $columnLetter . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $offset = ($col - 3) % 3;
                    if ($offset == 0) { // SL
                        $sheet->getStyle($columnLetter . '4:' . $columnLetter . $highestRow)->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    } else { // Thành tiền (offset 1) hoặc Miễn giảm (offset 2)
                        $sheet->getStyle($columnLetter . '4:' . $columnLetter . $highestRow)->getNumberFormat()
                            ->setFormatCode('#,##0');
                    }
                }

                // Borders
                $sheet->getStyle('A1:' . $lastColumn . $highestRow)->getBorders()
                    ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Row 3 will be the headings provided by headings() if we don't start at row 1.
            // But after my registerEvents, it is at row 3.
        ];
    }
}
