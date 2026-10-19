<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Services\BHYT\BacSiYLenhQuery;

use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BacSiYLenhExport;

class ReportBHYTController extends Controller
{
    public function indexBacSiYLenh()
    {   
        return view('bhyt.reports.report-bac-si-y-lenh');
    }

    public function fetchDataBacSiYLenh(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $query = BacSiYLenhQuery::build(
            $request->input('date_type', 'date_yl'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        return Datatables::of($query)
            ->filter(function ($instance) use ($request) {
                $search = $request->input('search.value'); // ô search mặc định của DataTables

                if (!empty($search)) {
                    $instance->where(function ($q) use ($search) {
                        $q->where('t.ma_khoa', 'like', "%{$search}%")
                        ->orWhere('t.ten_khoa', 'like', "%{$search}%")
                        ->orWhere('t.ma_bac_si', 'like', "%{$search}%")
                        ->orWhere('t.ho_ten', 'like', "%{$search}%");
                        // Nếu muốn search cả số lượng:
                        //->orWhere(DB::raw('COUNT(*)'), 'like', "%{$search}%"); // thường không cần
                    });
                }
            }, true) // true = báo cho Yajra là mình đã xử lý global search rồi
            ->make(true);
    }

    public function exportBacSiYLenhData(Request $request)
    {
        $fileName = 'bac_si_y_lenh_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new BacSiYLenhExport($request), $fileName);
    }
}
