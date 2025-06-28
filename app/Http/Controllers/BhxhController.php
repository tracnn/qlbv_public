<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\BhxhEmrPermission;
use DB;

class BhxhController extends Controller
{
    public function index()
    {
        return view('bhxh.index');
    }

    public function listEmrChecker(Request $request)
    {
        $data = BhxhEmrPermission::where('allow_view_at', '>=', now())->pluck('treatment_code');
        
        //truncate data
        $data = $data->chunk(500);
        $results = [];
        foreach ($data as $chunk) {
            $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
            ->join('his_department as last_department', 'last_department.id', '=', 'his_treatment.last_department_id')
            ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
            ->select('treatment_code', 'tdl_patient_name', 'tdl_patient_dob', 'tdl_patient_address',
                'tdl_patient_mobile', 'tdl_patient_phone', 'tdl_patient_relative_mobile',
                'tdl_patient_relative_phone', 'treatment_type_name',
                'last_department.department_name as last_department',
                'patient_type_name', 'tdl_patient_code', 'his_treatment.tdl_hein_card_number',
                'in_time', 'out_time', 'fee_lock_time', 'treatment_end_type_id')
            ->whereIn('treatment_code', $chunk);
            $results = $result->merge($result);
        }
        
        return Datatables::of($results)
            ->make(true);
    }
}
