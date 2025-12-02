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

        $date_type = $request->input('date_type', 'date_payment');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');

        // Nếu thiếu khoảng thời gian thì trả về rỗng cho an toàn
        if (empty($dateFrom) || empty($dateTo)) {
            return Datatables::of(collect())->make(true);
        }

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

        // Convert date format to 'Y-m-d H:i:s' cho created_at, updated_at (timestamp)
        $formattedDateFromForTimestamp = $dateFrom; // đã là Y-m-d H:i:s
        $formattedDateToForTimestamp   = $dateTo;   // đã là Y-m-d H:i:s

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
                // mặc định lọc theo ngày thanh toán (hoặc đổi thành ngay_yl nếu anh muốn)
                $dateField         = 'ngay_yl';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo   = $formattedDateToForFields;
                break;
        }

        /**
         * 1) Lấy dữ liệu từ XML2 bằng Eloquent
         */
        $xml2Rows = Qd130Xml2::select(
                'qd130_xml2s.ma_khoa',
                'medical_staffs.ten_khoa',
                'qd130_xml2s.ma_bac_si',
                'medical_staffs.ho_ten'
            )
            ->leftJoin('medical_staffs', 'medical_staffs.macchn', '=', 'qd130_xml2s.ma_bac_si')
            ->whereBetween("qd130_xml2s.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->get();

        /**
         * 2) Lấy dữ liệu từ XML3 bằng Eloquent
         */
        $xml3Rows = Qd130Xml3::select(
                'qd130_xml3s.ma_khoa',
                'medical_staffs.ten_khoa',
                'qd130_xml3s.ma_bac_si',
                'medical_staffs.ho_ten'
            )
            ->leftJoin('medical_staffs', 'medical_staffs.macchn', '=', 'qd130_xml3s.ma_bac_si')
            ->whereBetween("qd130_xml3s.$dateField", [$formattedDateFrom, $formattedDateTo])
            ->get();

        /**
         * 3) Gộp 2 collection lại (tương đương UNION ALL)
         */
        $merged = $xml2Rows->merge($xml3Rows);

        /**
         * 4) Group theo ma_khoa, ten_khoa, ma_bac_si, ho_ten và đếm số dòng (COUNT(*))
         */
        $grouped = $merged
            ->groupBy(function ($row) {
                return implode('|', [
                    $row->ma_khoa,
                    $row->ten_khoa,
                    $row->ma_bac_si,
                    $row->ho_ten,
                ]);
            })
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'ma_khoa'  => $first->ma_khoa,
                    'ten_khoa' => $first->ten_khoa,
                    'ma_bac_si'=> $first->ma_bac_si,
                    'ho_ten'   => $first->ho_ten,
                    'tong_so'  => $group->count(), // COUNT(*)
                ];
            })
            ->values(); // reset index 0,1,2,...

        /**
         * 5) Trả về cho DataTables từ Collection
         */
        return Datatables::of($grouped)->make(true);
    }
          
}
