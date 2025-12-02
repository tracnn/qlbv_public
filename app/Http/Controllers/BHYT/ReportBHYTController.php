<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml3;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $date_type = $request->input('date_type', 'date_yl');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');

        // Nếu ngày ở dạng YYYY-MM-DD thì chuẩn hoá về Y-m-d H:i:s
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)
                ->startOfDay()
                ->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)
                ->endOfDay()
                ->format('Y-m-d H:i:s');
        }

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' cho các field kiểu chuỗi YmdHi
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
        $formattedDateToForFields   = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

        // Timestamp giữ nguyên dạng Y-m-d H:i:s
        $formattedDateFromForTimestamp = $dateFrom;
        $formattedDateToForTimestamp   = $dateTo;

        // Chọn field ngày và format tương ứng theo date_type
        switch ($date_type) {
            case 'date_in':
                $dateField         = 'ngay_vao';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField         = 'ngay_ra';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField         = 'ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField         = 'created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo   = $formattedDateToForTimestamp;
                break;
            case 'date_update':
                $dateField         = 'updated_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo   = $formattedDateToForTimestamp;
                break;
            default:
                // mặc định lọc theo ngày y lệnh
                $dateField         = 'ngay_yl';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
        }

        // ---------- SUBQUERY 1: XML2 ----------
        $subQueryXml2 = DB::table('qd130_xml2s as q')
            ->leftJoin('medical_staffs as ms', 'ms.macchn', '=', 'q.ma_bac_si')
            ->whereBetween("q.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->select([
                DB::raw("COALESCE(ms.ma_khoa, '') as ma_khoa"),
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'q.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
            ]);

        // ---------- SUBQUERY 2: XML3 + UNION ALL ----------
        $subQueryUnion = DB::table('qd130_xml3s as q')
            ->leftJoin('medical_staffs as ms', 'ms.macchn', '=', 'q.ma_bac_si')
            ->whereBetween("q.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->select([
                DB::raw("COALESCE(ms.ma_khoa, '') as ma_khoa"),
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'q.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
            ])
            ->unionAll($subQueryXml2);

        // ---------- WRAP SUBQUERY LẠI RỒI GROUP BY ----------
        $query = DB::table(DB::raw("({$subQueryUnion->toSql()}) as t"))
            ->mergeBindings($subQueryUnion)
            ->select([
                't.ma_khoa',
                't.ten_khoa',
                't.ma_bac_si',
                't.ho_ten',
                DB::raw('COUNT(*) as tong_so'),
            ])
            ->groupBy(
                't.ma_khoa',
                't.ten_khoa',
                't.ma_bac_si',
                't.ho_ten'
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
