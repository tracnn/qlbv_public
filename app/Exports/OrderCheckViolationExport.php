<?php

namespace App\Exports;

use App\Services\OrderCheck\ViolationQueryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderCheckViolationExport implements FromCollection, WithHeadings, WithMapping
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function collection()
    {
        $service = new ViolationQueryService();
        return $service->filtered(new Request($this->params))
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Thời điểm', 'Mức độ', 'Mã luật', 'Mã ĐT', 'Mã BN', 'Tên BN', 'Bác sĩ', 'Khoa (ID)', 'Nội dung', 'Trạng thái', 'Người xử lý', 'Ghi chú'];
    }

    public function map($v): array
    {
        return [
            (string) $v->detected_at,
            $v->severity,
            $v->rule_code,
            $v->treatment_code,
            $v->patient_code,
            $v->patient_name,
            $v->doctor_username ?: $v->doctor_loginname,
            $v->department_id,
            $v->message,
            $v->status,
            $v->processed_by,
            $v->note,
        ];
    }
}
