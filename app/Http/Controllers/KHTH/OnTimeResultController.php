<?php
// app/Http/Controllers/KHTH/OnTimeResultController.php
namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\OnTimeResultService;
use App\Exports\OnTimeResultExport;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class OnTimeResultController extends Controller
{
    protected $service;

    public function __construct(OnTimeResultService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('khth.on-time-result');
    }

    private function validateDates(Request $request)
    {
        $request->validate(['date_from' => 'required|date', 'date_to' => 'required|date']);
    }

    public function getSummary(Request $request)
    {
        $this->validateDates($request);
        return response()->json($this->service->getSummaryData($request));
    }

    public function rooms(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomsSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));
        return response()->json($rows); // lowercase keys: room_id, execute_room_name
    }

    public function fetch(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildDetailSqlAndBindings($request);
        $results = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));

        $service = $this->service;
        return Datatables::of($results)
            ->editColumn('intruction_time', function ($r) { return strtodatetime($r->intruction_time); })
            ->editColumn('finish_time', function ($r) { return $r->finish_time ? strtodatetime($r->finish_time) : ''; })
            ->addColumn('actual_minutes_fmt', function ($r) { return is_null($r->actual_minutes) ? '' : round($r->actual_minutes) . ' phút'; })
            ->addColumn('chenh_lech', function ($r) { return (is_null($r->actual_minutes) || empty($r->estimate_duration)) ? '' : round($r->actual_minutes - $r->estimate_duration) . ' phút'; })
            ->addColumn('trang_thai', function ($r) use ($service) {
                $map = [
                    'dung_hen'  => '<span class="label label-success">Đúng hẹn</span>',
                    'tre_hen'   => '<span class="label label-danger">Trễ hẹn</span>',
                    'chua_tra'  => '<span class="label label-warning">Chưa trả KQ</span>',
                    'bat_thuong'=> '<span class="label label-default">Bất thường</span>',
                    'khong_hen' => '<span class="label label-info">Không có hẹn</span>',
                ];
                return $map[$service->classify($r)] ?? '';
            })
            ->rawColumns(['trang_thai'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $this->validateDates($request);
        $fileName = 'tra_kq_dung_hen_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new OnTimeResultExport($request->all()), $fileName);
    }
}
