<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml3;

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
        
        $date_type = $request->input('date_type');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return [];
    }
          
}
