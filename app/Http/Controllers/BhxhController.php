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
                return '<a href="' . route('bhxh.emr-checker-detail', 
                ['treatment_code' => $row->treatment_code]) . '" class="btn-sm btn-primary">Chi tiết EMR</a>';
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
}
