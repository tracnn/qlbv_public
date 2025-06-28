<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\BhxhEmrPermission;
use DB;
use Illuminate\Support\Facades\Crypt;

class BhxhController extends Controller
{
    public function index()
    {
        return view('bhxh.index');
    }

    public function listEmrChecker(Request $request)
    {
        $data = BhxhEmrPermission::where('allow_view_at', '>=', now())->pluck('treatment_code');
    
        if ($data->isEmpty()) {
            return Datatables::of(collect())->make(true);
        }
    
        $chunks = $data->chunk(1000);
    
        $firstQuery = null;
    
        foreach ($chunks as $chunk) {
            $query = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
                ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
                ->join('his_department as last_department', 'last_department.id', '=', 'his_treatment.last_department_id')
                ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
                ->select(
                    'treatment_code',
                    'tdl_patient_name',
                    'tdl_patient_dob',
                    'tdl_patient_address',
                    'tdl_patient_mobile',
                    'tdl_patient_phone',
                    'tdl_patient_relative_mobile',
                    'tdl_patient_relative_phone',
                    'treatment_type_name',
                    'last_department.department_name as last_department',
                    'patient_type_name',
                    'tdl_patient_code',
                    'his_treatment.tdl_hein_card_number',
                    'in_time',
                    'out_time',
                    'fee_lock_time',
                    'treatment_end_type_id'
                )
                ->whereIn('treatment_code', $chunk);
    
            if (is_null($firstQuery)) {
                $firstQuery = $query;
            } else {
                $firstQuery = $firstQuery->unionAll($query);
            }
        }
    
        return Datatables::of($firstQuery)
            ->editColumn('tdl_patient_dob', function ($row) {
                return dob($row->tdl_patient_dob);
            })
            ->editColumn('in_time', function ($row) {
                return strtodatetime($row->in_time);
            })
            ->editColumn('out_time', function ($row) {
                return strtodatetime($row->out_time);
            })
            ->editColumn('fee_lock_time', function ($row) {
                return strtodatetime($row->fee_lock_time);
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('bhxh.emr-checker-detail', ['treatment_code' => $row->treatment_code]) . '" class="btn-sm btn-primary">Chi tiết EMR</a>';
            })
            ->make(true);
    }

    public function emrCheckerDetail(Request $request)
    {
        $treatment_code = $request->input('treatment_code');
        return view('bhxh.detail', compact('treatment_code'));
    }

    public function emrCheckerDocumentList(Request $request)
    {
        $treatment_code = $request->input('treatment_code');
        $document_type_id = $request->input('document_type');

        if(empty($treatment_code))
        {
            return response()->json(['error' => 'Mã điều trị không tồn tại'], 400);
        }

        $checkPermission = BhxhEmrPermission::where('treatment_code', $treatment_code)->first();
        if(empty($checkPermission))
        {
            return response()->json(['error' => 'Mã điều trị không tồn tại'], 400);
        }
    
        $query = DB::connection('EMR_RS')
            ->table('emr_document')
            ->select(
                'emr_document.id',
                'emr_document.document_name',
                'emr_document.document_code',
                'emr_document_type.document_type_name',
                'emr_document.create_date',
                'emr_document.treatment_code'
            )
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->where('emr_document.treatment_code', $treatment_code);
    
        if (!empty($document_type_id)) {
            $query->where('emr_document.document_type_id', $document_type_id);
        }
    
        $data = $query->get();
    
        return Datatables::of($data)
        ->editColumn('create_date', function ($row) {
            return strtodate($row->create_date);
        })
        ->addColumn('action', function ($row) {
            $createdAt = now()->timestamp;
            $expiresIn = 7200;
            $token = Crypt::encryptString($row->document_code . '|' . $row->treatment_code . '|' . $createdAt . '|' . $expiresIn);
            return '<a href="' . route('secure-view-doc', ['token' => $token]) . '" class="btn-sm btn-primary" target="_blank">
            Xem PDF</a>';
        })
        ->make(true);
    }
}
