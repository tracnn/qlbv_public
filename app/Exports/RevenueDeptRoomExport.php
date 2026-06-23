<?php
// app/Exports/RevenueDeptRoomExport.php
namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Services\RevenueDeptRoomService;
use DB;

class RevenueDeptRoomExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $service;
    protected $rowNumber = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->service = new RevenueDeptRoomService();
    }

    public function collection()
    {
        $request = new Request($this->filters);
        list($sql, $binds) = $this->service->buildRoomDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));
        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['STT', 'Khoa', 'Loại phòng', 'Phòng', 'Doanh thu (VNĐ)', 'Số lượng'];
    }

    public function map($r): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $r->department_name,
            $r->room_type_name,
            $r->room_name,
            (float) $r->thanh_tien,
            (float) $r->so_luong,
        ];
    }
}
