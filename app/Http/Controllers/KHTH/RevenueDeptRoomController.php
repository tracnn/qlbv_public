<?php
// app/Http/Controllers/KHTH/RevenueDeptRoomController.php
namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\RevenueDeptRoomService;
use App\Exports\RevenueDeptRoomExport;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class RevenueDeptRoomController extends Controller
{
    protected $service;

    public function __construct(RevenueDeptRoomService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('khth.revenue-dept-room');
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

    public function departments(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildDepartmentsSqlAndBindings($request);
        return response()->json($this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds)));
    }

    public function rooms(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomsSqlAndBindings($request);
        return response()->json($this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds)));
    }

    public function roomTypes(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomTypesSqlAndBindings($request);
        return response()->json($this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds)));
    }

    public function fetch(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));

        return Datatables::of($rows)
            ->editColumn('thanh_tien', function ($r) { return number_format($r->thanh_tien); })
            ->editColumn('so_luong', function ($r) { return number_format($r->so_luong); })
            ->make(true);
    }

    public function export(Request $request)
    {
        $this->validateDates($request);
        $fileName = 'doanh_thu_khoa_phong_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new RevenueDeptRoomExport($request->all()), $fileName);
    }
}
