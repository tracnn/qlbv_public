<?php

namespace App\Http\Controllers\Emr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use Carbon\Carbon;
use Yajra\Datatables\Datatables;

use App\Services\CheckEmrService;
use App\Models\BhxhEmrPermission;

class EmrCheckerController extends Controller
{
    protected $checkEmrService;

    // Inject CheckService vào controller
    public function __construct(CheckEmrService $checkEmrService)
    {
        $this->checkEmrService = $checkEmrService;
    }
    
    public function indexEmrChecker()
    {
        return view('emr-checker.emr-checker-index');
    }
    public function listEmrChecker(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $treatment_code = $request->input('treatment_code');
        $date_type = $request->input('date_type');
        $department_catalog = $request->input('department_catalog');
        $patient_type = $request->input('patient_type');
        $treatment_type = $request->input('treatment_type');
        $treatment_end_type = $request->input('treatment_end_type');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Check and convert date format
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'his_treatment.in_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField = 'his_treatment.out_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField = 'his_treatment.fee_lock_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField = 'his_patient.create_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            default:
                $dateField = 'his_treatment.fee_lock_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
        }

        if ($treatment_code) {
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
                ->where('treatment_code', $treatment_code);
        } else {
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
                ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

            // Add department_catalog condition if it's provided
            if (!empty($department_catalog)) {
                // Directly embedding the variable into the query string (make sure this is safe)
                $result = $result->where('last_department_id', $department_catalog);
            }

            // Add patient_type condition if it's provided
            if (!empty($patient_type)) {
                // Directly embedding the variable into the query string (make sure this is safe)
                $result = $result->where('tdl_patient_type_id', $patient_type);
            }

            // Add treatment_type condition if it's provided
            if (!empty($treatment_type)) {
                // Directly embedding the variable into the query string (make sure this is safe)
                $result = $result->where('tdl_treatment_type_id', $treatment_type);
            }

            // Add treatment_type condition if it's provided
            if (!empty($treatment_end_type)) {
                // Directly embedding the variable into the query string (make sure this is safe)
                $result = $result->where('treatment_end_type_id', $treatment_end_type);
            }
        }

        return Datatables::of($result)
        ->editColumn('tdl_patient_dob', function($result) {
            return dob($result->tdl_patient_dob);
        })
        ->editColumn('in_time', function($result) {
            return strtodatetime($result->in_time);
        })
        ->editColumn('out_time', function($result) {
            return strtodatetime($result->out_time);
        })
        ->editColumn('fee_lock_time', function($result) {
            return strtodatetime($result->fee_lock_time);
        })
        ->addColumn('phone', function ($result) {
            return $result->tdl_patient_mobile ?? $result->tdl_patient_phone ?? $result->tdl_patient_relative_mobile ?? $result->tdl_patient_relative_phone;
        })
        ->addColumn('action', function ($result) {
            $createdAt = now()->timestamp;
                $expiresIn = 7200;
                $token = Crypt::encryptString($row->treatment_code . '|' . $createdAt . '|' . $expiresIn);
    
                $linkDetail = '<a href="' . route('treatment-result.search', [
                    'treatment_code' => $result->treatment_code
                ]) . '" class="btn-sm btn-primary">Chi tiết</a> ';
    
                $linkMergePdf = '<a href="' . route('view-merge-pdf', [
                    'token' => $token
                ]) . '" class="btn-sm btn-primary" target="_blank">Gộp PDF</a>';
    
                return $linkDetail . $linkMergePdf;
        })
        ->toJson();
    }

    public function indexEmrCheckerDetail()
    {
        return view('emr-checker.emr-checker-detail');
    }

    public function fetchDataCheckDetail(Request $request)
    {
        $treatment_code = $request->input('treatment_code');

        if ($treatment_code) {
             // Lấy thông tin chi tiết từ service
            $results = $this->checkEmrService->getTreatmentDetails($treatment_code);
            
             // Trả về view đã được render
            $html = view('emr-checker.partials.treatment-detail', compact('results'))->render();

            if ($results->isNotEmpty()) {
                //Kiểm tra nghiệp vụ
                $html .= $this->getPartialViewBasedOnPermission($treatment_code);
            }

            // Hiển thị đơn thuốc ngoại trú nếu có quyền
            if (auth()->user()->can('emr-check-medicine-outpatient')) {
                $medicine_results = $this->checkEmrService->getMedicineOutpatientDetails($treatment_code);

                if ($medicine_results->isNotEmpty()) {
                    $html .= view('emr-checker.partials.medicine-outpatient-detail', compact(
                        'medicine_results', 'results'))
                    ->render();
                }
            }
            
            return response($html);
        } else {
            return response()->json(['error' => 'Treatment code is required'], 400);
        }
    }

    public function getPartialViewBasedOnPermission($treatment_code)
    {
        $html = '';

        // Kiểm tra quyền 'emr-check-bangke' và 'emr-check-bangke-signer'
        if (auth()->user()->can('emr-check-bangke')) {
            $html .= $this->checkEmrService->checkBangKeAndSigner($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-accountant'
        if (auth()->user()->can('emr-check-accountant')) {
            $html .= $this->checkEmrService->checkAccountant($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-general-info'
        if (auth()->user()->can('emr-check-general-info')) {
            $html .= $this->checkEmrService->checkGeneralInfo($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-bbhc-info'
        if (auth()->user()->can('emr-check-bbhc-info')) {
            $html .= $this->checkEmrService->checkBbhcInfo($treatment_code);
        }

        // Kiểm tra quyền 'emr-check-advance-info'
        if (auth()->user()->can('emr-check-advance-info')) {
            $html .= $this->checkEmrService->checkAdvanceInfo($treatment_code);
        }

        // Nếu không có quyền nào được kiểm tra
        if (empty($html)) {
            $messages = $this->checkEmrService->getMessages();
            $html = $messages['no_permission']['error'];
        }

        return $html;
    }

    public function setPermission(Request $request)
    {
        $treatmentCodes = $request->input('treatment_codes', []);
        $expireDate = $request->input('expire_date');
    
        if (empty($treatmentCodes) || !$expireDate) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        }
    
        if (now()->gt($expireDate)) {
            return response()->json(['success' => false, 'message' => 'Ngày hết hạn phải lớn hơn hiện tại.']);
        }
    
        try {
            DB::beginTransaction();
    
            $now = now();
    
            foreach (array_chunk($treatmentCodes, 1000) as $chunk) {
                // Lấy dữ liệu chi tiết từ Oracle theo từng batch 1000
                $treatmentData = DB::connection('HISPro')
                    ->table('his_treatment')
                    ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
                    ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
                    ->join('his_department as last_department', 'last_department.id', '=', 'his_treatment.last_department_id')
                    ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
                    ->leftJoin('his_treatment_end_type', 'his_treatment_end_type.id', '=', 'his_treatment.treatment_end_type_id')
                    ->whereIn('his_treatment.treatment_code', $chunk)
                    ->select(
                        'his_treatment.treatment_code',
                        'his_patient.id as patient_id',
                        'his_treatment.tdl_patient_code as patient_code',
                        'his_treatment.tdl_patient_name as patient_name',
                        'his_treatment.tdl_patient_dob as patient_dob',
                        'his_treatment.tdl_patient_address as patient_address',
                        'his_treatment.tdl_treatment_type_id as treatment_type_id',
                        'his_treatment_type.treatment_type_name',
                        'his_treatment.tdl_patient_type_id as patient_type_id',
                        'his_patient_type.patient_type_name',
                        'his_treatment.tdl_hein_card_number as hein_card_number',
                        'his_treatment.last_department_id',
                        'last_department.department_name as last_department_name',
                        'his_treatment.in_time',
                        'his_treatment.out_time',
                        'his_treatment.fee_lock_time',
                        'his_treatment.treatment_end_type_id as treatment_end_type_id',
                        'his_treatment_end_type.treatment_end_type_name'
                    )
                    ->get();
    
                foreach ($treatmentData as $treatment) {
                    BhxhEmrPermission::updateOrCreate(
                        ['treatment_code' => $treatment->treatment_code],
                        [
                            'allow_view_at' => $expireDate,
                            'patient_id' => $treatment->patient_id,
                            'patient_code' => $treatment->patient_code,
                            'patient_name' => $treatment->patient_name,
                            'patient_dob' => $treatment->patient_dob,
                            'patient_address' => $treatment->patient_address,
                            'treatment_type_id' => $treatment->treatment_type_id,
                            'treatment_type_name' => $treatment->treatment_type_name,
                            'patient_type_id' => $treatment->patient_type_id,
                            'patient_type_name' => $treatment->patient_type_name,
                            'hein_card_number' => $treatment->hein_card_number,
                            'last_department_id' => $treatment->last_department_id,
                            'last_department_name' => $treatment->last_department_name,
                            'in_time' => $treatment->in_time,
                            'out_time' => $treatment->out_time,
                            'fee_lock_time' => $treatment->fee_lock_time,
                            'treatment_end_type_id' => $treatment->treatment_end_type_id,
                            'treatment_end_type_name' => $treatment->treatment_end_type_name
                        ]
                    );
                }
            }
    
            DB::commit();
    
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
        }
    }

    public function indexEmrCheckerBhxh()
    {
        return view('emr-checker.emr-checker-bhxh-index');
    }

    public function listEmrCheckerBhxh(Request $request)
    {
        $treatment_code = $request->input('treatment_code');
        $date_type = $request->input('date_type');
        $department_catalog = $request->input('department_catalog');
        $patient_type = $request->input('patient_type');
        $treatment_type = $request->input('treatment_type');
        $treatment_end_type = $request->input('treatment_end_type');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Check and convert date format
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'in_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField = 'out_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField = 'fee_lock_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField = 'created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            default:
                $dateField = 'fee_lock_time';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
        }

        $result = BhxhEmrPermission::select('treatment_code', 'patient_name', 'patient_dob', 'patient_address',
            'treatment_type_name', 'patient_type_name', 'hein_card_number', 'last_department_name',
            'in_time', 'out_time', 'fee_lock_time', 'allow_view_at', 'patient_code', 'treatment_end_type_name',
            'treatment_end_type_id', 'created_at', 'updated_at')
            ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

        if ($treatment_code) {
            $result = $result->where('treatment_code', $treatment_code);
        }

        if ($department_catalog) {
            $result = $result->where('last_department_id', $department_catalog);
        }

        if ($patient_type) {
            $result = $result->where('patient_type_id', $patient_type);
        }

        if ($treatment_type) {
            $result = $result->where('treatment_type_id', $treatment_type);
        }

        if ($treatment_end_type) {
            $result = $result->where('treatment_end_type_id', $treatment_end_type);
        }

        return Datatables::of($result)
            ->editColumn('patient_dob', function($result) {
                return dob($result->patient_dob);
            })
            ->editColumn('in_time', function($result) {
                return strtodatetime($result->in_time);
            })
            ->editColumn('out_time', function($result) {
                return strtodatetime($result->out_time);
            })
            ->editColumn('fee_lock_time', function($result) {
                return strtodatetime($result->fee_lock_time);
            })
            ->addColumn('action', function ($result) {
                $buttons = '
                    <a href="' .route('treatment-result.search',['treatment_code'=>$result->treatment_code]) .'" class="btn btn-sm btn-primary">
                        <span class="glyphicon glyphicon-eye-open"></span> Chi tiết EMR</a>
                ';
            })
            ->toJson();
    }

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return response()->json(['message' => 'Không có bản ghi nào được chọn.'], 400);
        }
    
        $ids = array_chunk($ids, 1000);

        foreach ($ids as $id) {
            BhxhEmrPermission::whereIn('treatment_code', $id)->delete();
        }
    
        return response()->json(['message' => 'Xóa thành công.']);
    }
}