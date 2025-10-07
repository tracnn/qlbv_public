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
        $query = BhxhEmrPermission::where('allow_view_at', '>=', now());
    
        return Datatables::of($query)
            ->editColumn('patient_dob', function ($row) {
                return dob($row->patient_dob);
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
                $createdAt = now()->timestamp;
                $expiresIn = 7200;
                $token = Crypt::encryptString($row->treatment_code . '|' . $createdAt . '|' . $expiresIn);
    
                $linkDetail = '<a href="' . route('bhxh.emr-checker-detail', [
                    'treatment_code' => $row->treatment_code
                ]) . '" class="btn-sm btn-primary">Chi tiết</a> ';
    
                $linkMergePdf = '<a href="' . route('view-merge-pdf', [
                    'token' => $token
                ]) . '" class="btn-sm btn-primary" target="_blank">Gộp PDF</a>';

                $linkMergePdfFlip = '<a href="' . route('view-merge-pdf-flip', [
                    'token' => $token
                ]) . '" class="btn-sm btn-primary" target="_blank">PDF Flip</a>';
    
                return $linkDetail . $linkMergePdf . $linkMergePdfFlip;
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
    
        return Datatables::of($query)
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

    public function serviceCdhaList(Request $request)
    {
        $treatment_code = $request->input('treatment_code');

        if(empty($treatment_code))
        {
            return response()->json(['error' => 'Mã điều trị không tồn tại'], 400);
        }

        $checkPermission = BhxhEmrPermission::where('treatment_code', $treatment_code)->first();
        if(empty($checkPermission))
        {
            return response()->json(['error' => 'Mã điều trị không tồn tại'], 400);
        }
    
        $query = DB::connection('HISPro')  
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->select('his_sere_serv.id', 
            'his_sere_serv.tdl_service_name', 
            'his_sere_serv.tdl_intruction_time', 
            'his_service_req.tdl_patient_code'
        )
        ->where('his_sere_serv.is_delete', 0)
        ->where('his_sere_serv.tdl_service_type_id', 3)
        ->where('his_sere_serv.tdl_treatment_code', $treatment_code);
    
        return Datatables::of($query)
        ->addColumn('action', function ($value) {
            $url = config('organization.base_pacs_url') . $value->id;
            if (config('organization.pacs_url_suffix')) {
                $url .= config('organization.pacs_url_suffix') . $value->id;
            }

            $result = '<a href="' . $url . '" 
                class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer">
                  <i class="fa fa-film"></i> Xem
                </a>';
            return $result;
        })
        ->make(true);
    }
}
