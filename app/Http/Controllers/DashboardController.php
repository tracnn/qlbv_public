<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;

use DataTables;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function convertDate($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            $from_date = Carbon::parse($startDate)->format('YmdHis');
            $to_date = Carbon::parse($endDate)->format('YmdHis');
        } else {
            $now = Carbon::now();
            $from_date = $now->copy()->startOfDay()->format('YmdHis');
            $to_date = $now->copy()->endOfDay()->format('YmdHis');
        }
    
        return [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
    }

    public function treatmentDetail(Request $request)
    {
        return view('dashboard.treatment-detail');
    }

    public function doanhthuDetail(Request $request)
    {
        return view('dashboard.doanhthu-detail');
    }

    public function averageInpatientDetail(Request $request)
    {
        return view('dashboard.average-inpatient-detail');
    }

    public function serviceDetail(Request $request)
    {
        return view('dashboard.service-detail');
    }

    public function fetchTreatmentDetail(Request $request)
    {
        $current_date = $this->convertDate($request->input('from_date'), $request->input('to_date'));
        $data_type = $request->input('data_type');

        $query = DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
        ->select('his_treatment.treatment_code',
            'his_treatment.tdl_patient_code',
            'his_treatment.tdl_patient_name',
            'his_treatment.in_time',
            'his_treatment.out_time',
            'his_treatment.icd_code',
            'his_treatment.icd_name'
        )
        ->where('his_treatment.is_delete',0);

        switch ($data_type) {
            case 'treatment':
                $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']]);
                break;
            case 'newpatient':
                $query->whereBetween('his_patient.create_time', [$current_date['from_date'], $current_date['to_date']])
                ->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('his_patient.is_delete',0);
                break;
            case 'noitru':
                $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'));
                break;
            case 'ravien-kham':
                $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'));
                break;
            case 'ravien':
                $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']]);
                break;
            case 'chuyenvien':
                $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'));
                break;
            case 'ravien-noitru':
                $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'));
                break;
            case 'ravien-ngoaitru':
                $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('tdl_treatment_type_id', config('__tech.treatment_type_ngoaitru'));
                break;
            default:
                $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']]);
                break;
        }

        return DataTables::of($query)
        ->editColumn('in_time', function ($row) {
            return strtodatetime($row->in_time);
        })
        ->editColumn('out_time', function ($row) {
            return strtodatetime($row->out_time);
        })
        ->make(true);
    }

    public function fetchServiceDetail(Request $request)
    {
        $current_date = $this->convertDate($request->input('from_date'), $request->input('to_date'));
        $data_type = $request->input('data_type');

        $query = DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id')
        ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
        ->select('his_service_req.tdl_treatment_code',
            'his_service_req.tdl_patient_code',
            'his_service_req.tdl_patient_name',
            'his_treatment.in_time',
            'his_treatment.out_time',
            'his_sere_serv.tdl_service_name',
            'his_service_req.intruction_time',
            'his_service_req.request_username',
        )
        ->whereBetween('intruction_time', [$current_date['from_date'], $current_date['to_date']])
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0);

        switch ($data_type) {
            case 'phauthuat':
                $query->where('his_service_req.service_req_type_id', config('__tech.service_req_type_phauthuat'));
                break;
            case 'thuthuat':
                $query->where('his_service_req.service_req_type_id', config('__tech.service_req_type_thuthuat'));
                break;
            default:
                break;
        }

        return DataTables::of($query)
        ->editColumn('intruction_time', function ($row) {
            return strtodatetime($row->intruction_time);
        })
        ->editColumn('in_time', function ($row) {
            return strtodatetime($row->in_time);
        })
        ->editColumn('out_time', function ($row) {
            return strtodatetime($row->out_time);
        })
        ->make(true);
    }
}