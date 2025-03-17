<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\BHYTKiemTraHoSo;
use App\HISProBaoCaoQuanTri;

use Carbon\Carbon;
use DB;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $current_date = $this->currentDate();
        
        $doanhthu = $this->doanhthu($current_date['from_date'], $current_date['to_date']);
        
        $sum_doanhthu = $doanhthu->sum('thanh_tien');

        $treatment = $this->treatment($current_date['from_date'], $current_date['to_date']);
        $sum_treatment = $treatment->sum('so_luong');

        $newpatient = $this->newpatient($current_date['from_date'], $current_date['to_date']);
        $sum_newpatient = $newpatient->sum('so_luong');

        $noitru = $this->noitru($current_date['from_date'], $current_date['to_date']);
        $sum_noitru = $noitru->sum('so_luong');

        return view('home', 
            compact('sum_doanhthu', 'sum_treatment', 'sum_newpatient', 'sum_noitru'));
    }

    private function currentDate()
    {
        $current_date = Carbon::now()->format('Ymd');
        $from_date = $current_date . '000000';
        $to_date = $current_date . '235959';

        return [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
    }

    public function fetchNoitru(Request $request)
    {
        $current_date = $this->currentDate();
        $model = $this->noitru($current_date['from_date'], $current_date['to_date']);

        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        $backgroundColor[] = '';
        //$borderColor[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->department_name;
            $data[$key] = doubleval($value->so_luong);
            $backgroundColor[$key] = "rgba(" .rand(0,255) .',' .rand(0,255) .',' .rand(0,255) .',0.7)';
            //$borderColor[$key] = "rgba(" .rand(0,255) .',' .rand(0,255) .',' .rand(0,255) .')';
        }

        $returnData[] = array(
            'type' => 'bar',
            'title' => 'Điều trị nội trú',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => $borderColor,//"rgb(255, 129, 232)",
                    'backgroundColor' => $backgroundColor,//"rgb(93, 158, 178)",
                    'label' => "Tổng cộng: " . number_format($sum_sl),
                    'fill' => true
                ),
            )
        );  

        return json_encode($returnData);
    }

    private function noitru($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
        ->selectRaw('count(*) as so_luong,last_department_id,department_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
        ->where('his_treatment.is_delete',0)
        ->groupBy('last_department_id','department_name')
        ->orderBy('so_luong','desc')
        ->get();
    }

    private function newpatient($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->selectRaw('count(*) as so_luong,branch_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->whereBetween('his_patient.create_time', [$from_date, $to_date])
        ->where('his_patient.is_delete',0)
        ->groupBy('branch_name')
        ->get();
    }

    private function doanhthu($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_type', 'his_sere_serv.tdl_service_type_id', '=', 'his_service_type.id')
        ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_type_id,service_type_name')
        ->whereBetween('tdl_intruction_time', [$from_date, $to_date])
        ->where('his_sere_serv.is_delete', 0)
        ->groupBy('tdl_service_type_id','service_type_name')
        ->orderBy('thanh_tien','desc')
        ->get();
    }

    private function treatment($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->selectRaw('count(*) as so_luong,branch_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->where('his_treatment.is_delete',0)
        ->groupBy('branch_name')
        ->get();
    }

    public function xml_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
        ->table('his_treatment_bed_room')
        ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
        ->join('his_room','his_bed_room.room_id','=','his_room.id')
        ->join('his_department','his_room.department_id','=','his_department.id')
        ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
        ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
        ->selectRaw('count(*) as so_luong,his_department.department_name')
        ->whereNull('his_treatment_bed_room.remove_time')
        ->whereNull('his_co_treatment.id')
        ->where('his_bed_room.is_active',1)
        ->where('his_room.is_active',1)
        ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
        ->where('his_treatment_bed_room.is_delete',0)
        ->where( function($q) {
            $q->whereNull('out_time')
            ->orWhere('out_time', '>', date_format(now(),'YmdHis'));
        })
        ->groupBy('his_department.department_name')
        ->orderBy('so_luong','desc')
        ->get();

        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        $backgroundColor[] = '';
        //$borderColor[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->department_name;
            $data[$key] = doubleval($value->so_luong);
            $backgroundColor[$key] = "rgba(" .rand(0,255) .',' .rand(0,255) .',' .rand(0,255) .',0.7)';
            //$borderColor[$key] = "rgba(" .rand(0,255) .',' .rand(0,255) .',' .rand(0,255) .')';
        }
        $returnData[] = array(
            'type' => 'bar',
            'title' => 'BN tại các buồng điều trị',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => $borderColor,//"rgb(255, 129, 232)",
                    'backgroundColor' => $backgroundColor,//"rgb(93, 158, 178)",
                    'label' => "Tổng cộng: " . number_format($sum_sl),
                    'fill' => true
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function treatment_type_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('count(*) as so_luong,treatment_end_type_id')
            ->where('in_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('in_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->groupBy('treatment_end_type_id')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = config('__tech.treatment_end_type')[$value->treatment_end_type_id];
            $data[$key] = doubleval($value->so_luong);
        }
        $returnData[] = array(
            'type' => 'bar',
            'title' => 'Biểu đồ trạng thái điều trị',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => "rgb(255, 129, 232)",
                    'backgroundColor' => "rgb(77, 121, 255)",
                    'label' => "Tổng cộng: " . number_format($sum_sl),
                    'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function treatment_number_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('count(*) as so_luong,in_date')
            ->where('in_date', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->groupBy('in_date')
            ->orderBy('in_date','desc')
            ->take(config('__tech.number_in_chart'))
            ->orderBy('in_date')
            ->get();
        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = substr($value->in_date, 6,2) . '/' . substr($value->in_date, 4,2);
            $data[$key] = doubleval($value->so_luong);
        }
        $returnData[] = array(
            'type' => 'line',
            'title' => 'Biểu đồ so sánh số lượng hồ sơ theo ngày',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'borderColor' => "rgb(128, 123, 187)",
                    //'backgroundColor' => "rgb(128, 123, 187)",
                    'label' => "Tổng cộng: " . number_format($sum_sl) . ' / ' . config('__tech.number_in_chart') . ' ngày.',
                    'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function top_service_sl_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->selectRaw('sum(amount) as so_luong,tdl_request_username')
            ->where('tdl_intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('tdl_intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->whereIn('tdl_service_req_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt_ko_kham')))
            ->groupBy('tdl_request_username')
            ->orderBy('so_luong','desc')
            ->take(config('__tech.number_top_request_dvkt'))
            ->get();
        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->tdl_request_username;
            $data[$key] = doubleval($value->so_luong);
        }
        $returnData[] = array(
            'type' => 'bar',
            'title' => 'Top ' . config('__tech.number_top_request_dvkt') . ' BS ra y lệnh DVKT (theo số lượng)',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => "rgb(255, 129, 232)",
                    'backgroundColor' => "rgb(125, 93, 126)",
                    'label' => "Tổng cộng: " . number_format($sum_sl),
                    'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function top_service_st_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->selectRaw('sum(amount*price) as so_luong,tdl_request_username')
            ->where('tdl_intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('tdl_intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->whereIn('tdl_service_req_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt_ko_kham')))
            ->groupBy('tdl_request_username')
            ->orderBy('so_luong','desc')
            ->take(config('__tech.number_top_request_dvkt'))
            ->get();
        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->tdl_request_username;
            $data[$key] = doubleval($value->so_luong);
        }
        $returnData[] = array(
            'type' => 'bar',
            'title' => 'Top ' . config('__tech.number_top_request_dvkt') . ' BS ra y lệnh DVKT (theo số số tiền)',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => "rgb(255, 129, 232)",
                    'backgroundColor' => "rgb(125, 93, 126)",
                    'label' => "Tổng cộng: " . number_format($sum_sl),
                    'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function noitru_by_department_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
            ->selectRaw('count(*) as so_luong,department_name')
            ->where('in_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('in_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->groupBy('department_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_sl = $model->sum('so_luong');
        $sum_sl_khoa = $model->count('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->department_name;
            $data[$key] = doubleval($value->so_luong);
        }
        $returnData[] = array(
            'type' => 'bar',
            'title' => 'Bệnh nhân nhập viện theo khoa',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => "rgb(255, 129, 232)",
                    'backgroundColor' => "rgb(176, 145, 57)",
                    'label' => "Tổng cộng: " . number_format($sum_sl) .' BN / ' . $sum_sl_khoa . ' khoa',
                    'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

//
    public function noitru_by_patient_type_chart(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_patient_type', 'his_treatment.tdl_patient_type_id', '=', 'his_patient_type.id')
            ->selectRaw('count(*) as so_luong,patient_type_name')
            ->where('in_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('in_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->groupBy('patient_type_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_sl = $model->sum('so_luong');

        $labels[] = '';
        $data[] = '';
        foreach ($model as $key => $value) {
            $labels[$key] = $value->patient_type_name;
            $data[$key] = doubleval($value->so_luong);
            $backgroundColor[$key] = '#'. str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
        }
        $returnData[] = array(
            'type' => 'doughnut',
            'title' => 'Nhập viện theo đối tượng (Tổng cộng ' . number_format($sum_sl) . ' BN)',
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    //'borderColor' => "rgb(255, 129, 232)",
                    'backgroundColor' => $backgroundColor,
                    //'label' => "Tổng cộng: " . number_format($sum_sl) .' BN / ' . $sum_sl_khoa . ' khoa',
                    //'fill' => false
                ),
            )
        );  
        return json_encode($returnData);
    }

}
