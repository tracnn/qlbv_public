<?php
// app/Exports/OnTimeResultExport.php
namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Services\OnTimeResultService;
use DB;

class OnTimeResultExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $service;
    protected $rowNumber = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->service = new OnTimeResultService();
    }

    public function collection()
    {
        $request = new Request($this->filters);
        list($sql, $binds) = $this->service->buildDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));
        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['STT','Mã ĐT','Họ tên BN','Khoa/Phòng TH','Loại DV','Tên DV','Giờ chỉ định','Giờ trả KQ','TG thực tế (phút)','TG hẹn (phút)','Chênh lệch (phút)','Trạng thái'];
    }

    public function map($r): array
    {
        $this->rowNumber++;
        $statusLabel = [
            'dung_hen'=>'Đúng hẹn','tre_hen'=>'Trễ hẹn','chua_tra'=>'Chưa trả KQ','bat_thuong'=>'Bất thường',
        ];
        $cls = $this->service->classify($r);
        return [
            $this->rowNumber,
            $r->tdl_treatment_code,
            $r->tdl_patient_name,
            $r->execute_room_name,
            $r->service_type_name,
            $r->service_name,
            strtodatetime($r->intruction_time),
            $r->finish_time ? strtodatetime($r->finish_time) : '',
            is_null($r->actual_minutes) ? '' : round($r->actual_minutes),
            $r->estimate_duration,
            is_null($r->actual_minutes) ? '' : round($r->actual_minutes - $r->estimate_duration),
            $statusLabel[$cls] ?? '',
        ];
    }
}
