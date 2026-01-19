<?php

namespace App\Http\Controllers\Emr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Services\FtpService;
use Illuminate\Support\Facades\Crypt;
use setasign\Fpdi\Fpdi;

use DB;

use QrCode;
use App\Models\CheckBHYT\share_expire;
use DataTables;

use hisorange\BrowserDetect\Parser as Browser;

use App\Services\PdfFetchService;

class EmrController extends Controller
{
    protected $ParamDepartment;
    protected $department;

    protected $ParamTreatmentType;
    protected $TreatmentType;

    protected $ParamPatientType;
    protected $PatientType;

    protected $ParamDocumentType;
    protected $DocumentType;

    protected $pdfFetchService;

    public function __construct(PdfFetchService $pdfFetchService)
    {
        $this->pdfFetchService = $pdfFetchService;

        $this->searchParams = [
            'date' => [
            	'from' => date_format(now(),'Y-m-d'),
            	'to' => date_format(now(),'Y-m-d')
            ],
            'treatment_code' => null,
        ];

        $this->department = DB::connection('HISPro')
            ->table('his_department')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->ParamDepartment = [];

        $this->TreatmentType = DB::connection('HISPro')
            ->table('his_treatment_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->ParamTreatmentType = [];

        $this->PatientType = DB::connection('HISPro')
            ->table('his_patient_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->ParamPatientType = [];

        $this->DocumentType = DB::connection('EMR_RS')
            ->table('emr_document_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->ParamDocumentType = [];
    }

    private function __getSearchParam($request)
    {
        return [
            'date' => [
            	'from' => $request->get('date') ? $request->get('date')['from'] : null,
            	'to' => $request->get('date') ? $request->get('date')['to'] : null
            ],
            'treatment_code' => $request->get('treatment_code'),
        ];
    }

    private function __ParamDepartment($request)
    {
        return $request->get('department') ? $request->get('department') : $this->ParamDepartment;
    }

    private function __ParamTreatmentType($request)
    {
        return $request->get('treatment_type') ? $request->get('treatment_type') : $this->ParamTreatmentType;
    }
    private function __ParamPatientType($request)
    {
        return $request->get('patient_type') ? $request->get('patient_type') : $this->ParamPatientType;
    }
    private function __ParamDocumentType($request)
    {
        return $request->get('document_type') ? $request->get('document_type') : $this->ParamDocumentType;
    }

    public function index(Request $request)
    {
    	$params = $this->searchParams;
        $department = $this->department;
        $ParamDepartment = $this->ParamDepartment;

        $treatment_type = $this->TreatmentType;
        $ParamTreatmentType = $this->ParamTreatmentType;

        $patient_type = $this->PatientType;
        $ParamPatientType = $this->ParamPatientType;

    	//$emr_treatment = $this->list_emr_treatment($params, $ParamDepartment, $ParamTreatmentType, $ParamPatientType);
        //return $emr_treatment;
    	return view('emr.index',
    		compact('params','department','ParamDepartment',
                'treatment_type','ParamTreatmentType','patient_type','ParamPatientType')
    	);
    }

    public function search(Request $request)
    {
    	$params = $this->__getSearchParam($request);
        $department = $this->department;
        $ParamDepartment = $this->__ParamDepartment($request);

        $treatment_type = $this->TreatmentType;
        $ParamTreatmentType = $this->__ParamTreatmentType($request);

        $patient_type = $this->PatientType;
        $ParamPatientType = $this->__ParamPatientType($request);
    	
    	//$emr_treatment = $this->list_emr_treatment($params, $ParamDepartment, $ParamTreatmentType, $ParamPatientType);

        return view('emr.index',
            compact('params','department','ParamDepartment',
                'treatment_type','ParamTreatmentType','patient_type','ParamPatientType')
    	);
    }

    public function checkemr(Request $request)
    {
        $ma_van_ban_qt = array('01','02','30','31');

        $emr_treatment = $this->get_emr_treatment($request->treatment_code);
        $his_tracking = $this->get_tracking($request->treatment_code);

        $emr_document = $this->get_emr_document($request->treatment_code);

        $emr_document_ko_tao = $this->get_emr_document_ko_tao($request->treatment_code);

        $emr_document_uncomp = $this->get_emr_document_uncomp($request->treatment_code);

        $count_tracking_nosign = 0;
        $trackings = [];

        foreach ($his_tracking as $key_track => $value_track) {
            $a = 'HIS_TRACKING:' . $value_track->id;
            $pos = false;
            foreach ($emr_document as $key_doc => $value_doc) {
                $pos = strpos($value_doc->his_code, 'HIS_TRACKING:' . $value_track->id);
                if ($pos) {
                    break;
                }
            }
            if (!$pos) {
                $count_tracking_nosign ++;
            }
            $trackings[] = array('creator' => $value_track->creator,
                'tracking_time' => $value_track->tracking_time,
                'signed' => $pos);
        }

        return view('emr.check-emr',
            compact('trackings','emr_document_ko_tao','ma_van_ban_qt','count_tracking_nosign',
                'emr_document_uncomp','emr_treatment')
        );
    }

    public function list_emr_treatment(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $dateType = $request['dateType'] ? $request['dateType'] : 'date_in';

        $fromDate = $request['date_from'] ? date_format(date_create($request['date_from']),'Ymd000000') : 
        date_format(now(),'Ymd000000');
        $toDate = $request['date_to'] ? date_format(date_create($request['date_to']),'Ymd235959') : 
        date_format(now(),'Ymd235959');

         $query = DB::connection('HISPro')->table('his_treatment')
        ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
        ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
        ->leftJoin('his_treatment_result', 'his_treatment_result.id', '=', 'his_treatment.treatment_result_id')
        ->join('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
        ->leftJoin('his_treatment_end_type', 'his_treatment_end_type.id', '=', 'his_treatment.treatment_end_type_id')
        ->select('treatment_code', 'tdl_patient_code', 'tdl_patient_name', 'tdl_patient_dob', 'tdl_hein_card_number', 
                'treatment_type_name', 'patient_type_name', 'icd_code', 'in_time', 'out_time', 'end_code', 
                'treatment_result_name', 'treatment_end_type_name', 'department_code', 'department_name', 'is_pause',
                'tdl_patient_mobile', 'tdl_patient_phone', 'tdl_patient_relative_mobile', 'tdl_patient_relative_phone',
                'fee_lock_time', 'his_treatment.tdl_patient_type_id');

        // Điều kiện tìm kiếm
        if ($treatmentCode = $request->input('treatment_code')) {
            $query->where('treatment_code', $treatmentCode);
        } else {

            if ($departmentCodes = $request->input('department')) {
                $query->whereIn('department_code', $departmentCodes);
            }

            if ($treatmentTypes = $request->input('treatment_type')) {
                $query->whereIn('treatment_type_code', $treatmentTypes);
            }

            if ($patientTypes = $request->input('patient_type')) {
                $query->whereIn('patient_type_code', $patientTypes);
            }
            
            // Áp dụng điều kiện lọc dựa trên dateType
            $dateColumn = $dateType === 'date_in' ? 'in_time' : 'out_time';
            $query->whereBetween($dateColumn, [$fromDate, $toDate]);
        }
       

        return Datatables::of($query)
            ->editColumn('tdl_patient_dob', function($result) {
                return substr($result->tdl_patient_dob, 0, 4);
            })
            ->editColumn('in_time', function($result) {
                return strtodatetime($result->in_time);
            })
            ->editColumn('out_time', function($result) {
                return $result->out_time ? strtodatetime($result->out_time) : $result->out_time;
            })
            ->addColumn('temp_save', function($result) {
                // Check if out_time is not null AND is_pause is null, return a tick mark or empty string
                return !is_null($result->out_time) && is_null($result->is_pause) ? '&#10004;' : ''; // Using HTML entity for tick mark
            })
            ->addColumn('qr_token', function($result) {
                // Xác định số điện thoại dựa trên ưu tiên
                $phoneNumber = $result->tdl_patient_mobile 
                    ?? $result->tdl_patient_phone 
                    ?? $result->tdl_patient_relative_mobile 
                    ?? $result->tdl_patient_relative_phone 
                    ?? '';
                
                // Tạo token tương tự như trong treatment-result/index.blade.php
                $createdAt = now()->timestamp;
                $expiresIn = 7200;
                $token = Crypt::encryptString($result->treatment_code . '|' . $phoneNumber . '|' . $createdAt . '|' . $expiresIn);
                
                return $token;
            })
            ->addColumn('action', function ($result) {
                $phone = $result->tdl_patient_mobile 
                    ?? $result->tdl_patient_phone 
                    ?? $result->tdl_patient_relative_mobile 
                    ?? $result->tdl_patient_relative_phone 
                    ?? '';
                
                $createdAt = now()->timestamp;
                $expiresIn = 7200;
                $token = Crypt::encryptString("{$result->treatment_code}|{$phone}|{$createdAt}|{$expiresIn}");
                    
                $buttons = '<a href="' .route('emr.check-emr',['treatment_code'=>$result->treatment_code]) .'" class="btn btn-sm btn-danger" target="_blank">
                                <span class="glyphicon glyphicon-check"></span> Kiểm tra</a>
                            <a href="' .route('treatment-result.search',['treatment_code'=>$result->treatment_code]) .'" class="btn btn-sm btn-primary" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> Chi tiết EMR</a>
                            <a href="' .route('view-guide-content',['token'=> $token]) .
                            '"class="btn btn-sm btn-primary" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> Trả KQ</a>
                            <a href="' .route('system.user-function.search',['treatment_code'=>$result->treatment_code]) .'" class="btn btn-sm btn-warning" target="_blank">
                                <span class="glyphicon glyphicon-check"></span> </a>';
                // Kiểm tra nếu patient_type_id là 102 thì thêm nút Dữ liệu tiêm chủng
                if ($result->tdl_patient_type_id == 102 && (\Auth::user()->can('vaccination') || 
                    \Auth::user()->hasRole('superadministrator'))) {
                    $buttons .= ' <a href="' .route('vaccination.data', ['patient_code'=>$result->tdl_patient_code,
                        'treatment_code'=>$result->treatment_code]) .'" class="btn btn-sm btn-info" target="_blank">
                                    <span class="glyphicon glyphicon-plus"></span> Tiêm chủng </a>';
                }
                return $buttons;
            })
            ->setRowClass(function ($result) {
                // Apply 'highlight-red' if 'fee_lock_time' is not null
                if ($result->fee_lock_time !== null) {
                    return 'highlight-red';
                }
                // Otherwise, check if 'temp_save' is true and apply 'highlight-blue'
                if (!is_null($result->out_time) && is_null($result->is_pause)) {
                    return 'highlight-blue';
                }
                return ''; // Default, no class
            })
            ->toJson();
    }

    private function get_emr_treatment($treatment_code)
    {

        $emr_treatment = DB::connection('EMR_RS')
            ->table('emr_treatment')
            ->select('treatment_code', 'patient_code', 'vir_patient_name', 'dob', 'hein_card_number', 'patient_type_name', 'icd_code', 'in_time', 'out_time' ,'end_code', 'treatment_result_name', 'treatment_end_type_name', 'current_department_code', 'current_department_name')
            ->where('is_delete', 0)
            ->where('treatment_type_code', '03')
            ->where('treatment_code',$treatment_code);

        return $emr_treatment->get();
    }

    private function get_emr_document($treatment_code)
    {
        $result = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->select('*')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $treatment_code)
            ->where('his_code', 'like', '%TRACKING%')
            ->get();
        return $result;
    }

    private function get_emr_document_ko_tao($treatment_code)
    {
        $result_doc = DB::connection('EMR_RS')
            ->table('emr_document')
            ->select('document_type_id')
            ->where('emr_document.is_delete', 0)
            ->whereNotNull('document_type_id')
            ->where('emr_document.treatment_code', $treatment_code)
            ->groupBy('document_type_id')
            ->get();

        $result = DB::connection('EMR_RS')
            ->table('emr_document_type')
            ->whereNotIn('id', $result_doc->pluck('document_type_id'))
            ->get();

        return $result;
    }

    private function get_emr_document_uncomp($treatment_code)
    {
        $result = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->where('emr_document.is_delete', 0)
            ->whereNull('emr_document.is_capture')
            ->where(function($q){
                $q->whereNotNull('rejecter')
                    ->orWhereNotNull('next_signer')
                    ->orWhereNotNull('un_signers');
            })
            ->where('emr_document.treatment_code', $treatment_code)
            ->get();

        return $result;
    }

    private function get_tracking($treatment_code)
    {

        $result = DB::connection('HISPro')
            ->table('his_tracking')
            ->join('his_treatment', 'his_treatment.id', '=', 'his_tracking.treatment_id')
            ->select('his_tracking.*')
            ->where('his_tracking.is_delete', 0)
            ->where('his_treatment.treatment_code', $treatment_code)
            ->orderBy('tracking_time','desc')
            ->get();
        return $result;
    }

    public function TreatmentResultIndex(Request $request)
    {
        $params = $this->searchParams;
        $document_type = $this->DocumentType;
        $ParamDocumentType = $this->ParamDocumentType;

        return view('emr.treatment-result.index', 
            compact('params','document_type','ParamDocumentType')
        );
    }

    public function TreatmentResultSearch(Request $request)
    {
        $params = $this->__getSearchParam($request);

        $document_type = $this->DocumentType;
        $ParamDocumentType = $this->__ParamDocumentType($request);

        $emr_treatment = $this->get_treatment($params['treatment_code']);
        $emr_document = $this->get_documents($params['treatment_code'],$ParamDocumentType);
        $patient_info = $this->get_patient_info($params['treatment_code']);

        $sere_serv_cdha = $this->sere_serv_cdha($params['treatment_code']);

        return view('emr.treatment-result.index',
            compact('params','emr_treatment','emr_document','document_type','ParamDocumentType', 'patient_info', 
                'sere_serv_cdha')
        );
    }

    private function sere_serv_cdha($treatment_code)
    {
        return DB::connection('HISPro')  
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->select('his_sere_serv.id', 
            'his_sere_serv.tdl_service_name', 
            'his_sere_serv.tdl_intruction_time', 
            'his_service_req.tdl_patient_code'
        )
        ->where('his_sere_serv.is_delete', 0)
        ->where('his_sere_serv.tdl_service_type_id', 3)
        ->where('his_sere_serv.tdl_treatment_code', $treatment_code)
        ->get();
    }

    public function viewDocument(Request $request)
    {
        $check_expire = share_expire::where('active', 1)
                ->where('document_code', $request->document_code)
                ->first();
        
        if (empty($check_expire)) {
            return 'Tài liệu chưa được chia sẻ!!!';
        }

        if (strtotime(now()) - strtotime($check_expire->created_at) > 2592000 ) {
            return 'Đã hết hiệu lực chia sẻ tài liệu!!!';
        }

        activity()->log('View doc-share: ' . $check_expire->document_code);
        try {
            $result = DB::connection('EMR_RS')
            ->table('emr_version')
            ->join('emr_document', 'emr_document.id', '=', 'emr_version.document_id')
            ->where('emr_version.is_delete', 0)
            ->where('emr_version.is_active', 1)
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.document_code', $request->document_code)
            ->orderBy('emr_version.id', 'desc')
            ->first();

            if(!$result)
            {
                throw new \Exception('Invalid request');
            }
            
            $content = Storage::disk('emr')->get($result->url);

            // if (session('_token') && Storage::disk('public')->put('/upload/' .session('_token'), $content)) {
            //     $url_pdf = url('storage') .'/upload/' .session('_token');
            //     return redirect(url('/' .'vendor/pdfjsv2/web/viewer.html?file=' .$url_pdf));
            // } else {
            //     throw new \Exception('Invalid request');
            // }
            return response()->make($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="test.pdf"'
            ]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function viewDocByAdmin(Request $request)
    {
        try {

            $documentCode = $request->get('document_code');
            $treatmentCode = $request->get('treatment_code');

            // Mã hóa thông tin
            $token = Crypt::encryptString("{$documentCode}|{$treatmentCode}");
            $securePdfUrl = url('/api/secure-view-pdf?token=' . urlencode($token));

            //$pdfUrl = url('/api/view-pdf') . '?document_code=' . $request->get('document_code') . '&treatment_code=' . $request->get('treatment_code');
            //return redirect(url('/vendor/pdfjsv2/web/viewer.html?file=' . urlencode($pdfUrl)));  
            return redirect(url('/vendor/pdfjsv2/web/viewer.html?file=' . urlencode($securePdfUrl)));          
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function viewDocByToken(Request $request)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                abort(400, 'Thiếu token');
            }

            $decrypted = Crypt::decryptString($token);
            [$documentCode, $treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }  

            // Tạo link PDF đã mã hóa
            $tokenEncrypted = Crypt::encryptString("{$documentCode}|{$treatmentCode}|{$createdAt}|{$expiresIn}");
            $pdfUrl = url('/api/secure-view-pdf?token=' . urlencode($tokenEncrypted));
            return redirect('/vendor/pdfjsv2/web/viewer.html?file=' . urlencode($pdfUrl));
        } catch (\Exception $e) {
            abort(403, 'Token không hợp lệ');
        }
    }

    public function TreatmentResultQrCode(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        // Lấy base_url từ file config
        $baseUrl = config('organization.base_url');
        $url = $baseUrl . '/view-document?document_code=' . $request->document_code;
        
        $url = preg_match("#^https?\:\/\/#", $url) ? $url : "http://{$url}";
        // $qr->url($url .$request->document_code);
        // $qr->qrCode(300, $request->document_code);
        $img = QrCode::size(1200)
            ->format('png')
            //->merge(public_path('images/logo.png'))
             //->backgroundColor(225, 0, 0)
             //->color(0, 0, 255)
            ->generate($url);
        $filename = $request->document_code . '.png';
        $qr = Storage::disk('public')->put($filename, $img);

        if (!empty($qr)) {
            share_expire::where('active', 1)
                ->where('document_code', $request->document_code)
                ->update(['active' => 0]);
            $model = new share_expire;
            $model->active = 1;
            $model->document_code = $request->document_code;
            $model->save();

            $contents = Storage::disk('public')->get($request->document_code . '.png');

            return url('storage') . '/' . $request->document_code . '.png';

            // return response()->make($contents, 200, [
            //     'Content-Type' => 'png',
            //     'Content-Disposition' => 'inline; filename="download.png"'
            // ]);
        }

    }

    private function get_documents($treatment_code,$ParamDocumentType)
    {
        $result = DB::connection('EMR_RS')
            ->table('emr_document')
            ->leftJoin('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->select('emr_document.document_code','emr_document.document_name','emr_document_type.document_type_code','emr_document_type.document_type_name','emr_document.create_date','emr_document.treatment_code')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $treatment_code);
        if ($ParamDocumentType) {
            $result = $result->where(function($q) use ($ParamDocumentType) {
                $q->whereIn('document_type_code', $ParamDocumentType)
                ->orWhereNull('document_type_code');
            });
        }

        $result = $result->orderBy('emr_document_type.num_order', 'ASC');

        return $result->get();
    }

    private function get_treatment($treatment_code)
    {

        $emr_treatment = DB::connection('EMR_RS')
        ->table('emr_treatment')
        ->select('treatment_code', 'patient_code', 'vir_patient_name', 'dob', 'hein_card_number', 'patient_type_name', 'icd_code', 'in_time', 'out_time' ,'end_code', 'treatment_result_name', 'treatment_end_type_name', 'current_department_code', 'current_department_name')
        ->where('is_delete', 0)
        ->where('treatment_code',$treatment_code);

        return $emr_treatment->get();
    }

    private function get_patient_info($treatment_code)
    {

        $patient_info = DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
        ->select('his_treatment.treatment_code', 'his_patient.patient_code', 'his_patient.dob',
        'his_patient.vir_patient_name', 'his_patient.mobile', 'his_patient.phone',
        'his_treatment.in_time', 'his_treatment.out_time', 'his_treatment.tdl_hein_card_number', 
        'his_patient_type.patient_type_name', 'his_treatment.id')
        ->where('his_patient.is_delete', 0)
        ->where('treatment_code',$treatment_code);

        return $patient_info->get();
    }

    public function updatePhone(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        // $validated = $request->validate([
        //     'phoneNumber' => 'required|numeric',
        //     'patientCode' => 'required',
        // ]);
        
        if (!$request->patientCode || !$request->phoneNumber) {
            return ['maKetqua' => '401',
                'noiDung' => 'Lỗi dữ liệu'];
        }

        try {
            DB::connection('HISPro')
            ->table('his_patient')
            ->where('patient_code', $request->patientCode)
            ->update(['phone' => $request->phoneNumber
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        
        return ['maKetqua' => '200',
            'noiDung' => $request->phoneNumber];
    }

    public function viewMety(Request $request)
    {
        // Lấy dữ liệu
        $results = DB::connection('HISPro')
        ->table('his_service_req')
        ->select('his_service_req.id', 'his_service_req.intruction_time', 'his_service_req_mety.medicine_type_name',
            'his_service_req_mety.unit_name', 'his_service_req_mety.amount', 'his_service_req_mety.tutorial',
            'his_service_req.service_req_stt_id')
        ->join('his_service_req_mety', 'his_service_req.id', '=', 'his_service_req_mety.service_req_id')
        ->where('his_service_req_mety.is_delete', '=', 0)
        ->when($request->filled('treatment_id'), function ($query) use ($request) {
            return $query->where('his_service_req_mety.tdl_treatment_id', '=', $request->get('treatment_id'));
        })
        ->orderBy('his_service_req.intruction_time', 'DESC')
        ->get();

        // Tổ chức dữ liệu
        $organizedResults = [];

        foreach ($results as $result) {
            $organizedResults[$result->id]['his_service_req'] = $result; // Giả sử 'id' là ID của his_service_req
            $organizedResults[$result->id]['his_service_req_mety'][] = $result; // Lưu trữ his_service_req_mety vào một mảng
        }

        return view('emr.treatment-result.view-mety',
            compact('organizedResults')
        );
    }

    public function viewPdf(Request $request)
    {
        try {

            $result = DB::connection('EMR_RS')
                ->table('emr_version')
                ->join('emr_document', 'emr_document.id', '=', 'emr_version.document_id')
                ->where('emr_version.is_delete', 0)
                ->where('emr_version.is_active', 1)
                ->where('emr_document.is_delete', 0)
                ->where('emr_document.document_code', ($request->get('document_code')))
                ->where('emr_document.treatment_code', $request->get('treatment_code'))
                ->orderBy('emr_version.id', 'desc')
                ->first();

            if (!$result) {
                throw new \Exception('Invalid request');
            }

            $resultUrl = str_replace('\\', '/', $result->url);
            // $ftp = new FtpService();
            // $ftp->connect();
            // $content = $ftp->getContent($resultUrl);
            // $ftp->close();
            $baseUrl = config('organization.fss_config.baseUrl');
            $url = $baseUrl . $resultUrl;
            $pdfData = $this->pdfFetchService->getPdfAsBase64($url);
            $content = base64_decode($pdfData['base64']);

            //$content = Storage::disk('emr')->get($result->url);
            
            return response()->make($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }   

    public function securePdfView(Request $request)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                return response()->json(['error' => 'Thiếu token'], 400);
            }

            // Giải mã token để lấy document_code và treatment_code
            $decrypted = Crypt::decryptString($token);

            [$documentCode, $treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }  

            // Truy vấn lấy file PDF
            $result = DB::connection('EMR_RS')
                ->table('emr_version')
                ->join('emr_document', 'emr_document.id', '=', 'emr_version.document_id')
                ->where('emr_version.is_delete', 0)
                ->where('emr_version.is_active', 1)
                ->where('emr_document.is_delete', 0)
                ->where('emr_document.document_code', $documentCode)
                ->where('emr_document.treatment_code', $treatmentCode)
                ->orderBy('emr_version.id', 'desc')
                ->first();

            if (!$result) {
                throw new \Exception('Không tìm thấy kết quả');
            }

            $resultUrl = str_replace('\\', '/', $result->url);
            // $ftp = new FtpService();
            // $ftp->connect();
            // $content = $ftp->getContent($resultUrl);
            // $ftp->close();
            $baseUrl = config('organization.fss_config.baseUrl');
            $url = $baseUrl . $resultUrl;
            $pdfData = $this->pdfFetchService->getPdfAsBase64($url);
            
            // Decode base64 về binary PDF
            $content = base64_decode($pdfData['base64']);
            
            return response()->make($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Link không hợp lệ hoặc đã hết hạn'], 403);
        }
    }

    public function viewMergePdfByToken(Request $request)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                abort(400, 'Thiếu token');
            }

            $decrypted = Crypt::decryptString($token);
            [$treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }  

            // Tạo link PDF đã mã hóa
            $tokenEncrypted = Crypt::encryptString("{$treatmentCode}|{$createdAt}|{$expiresIn}");
            $pdfUrl = url('/api/merge-pdf-secure?token=' . urlencode($tokenEncrypted));
            return redirect('/vendor/pdfjsv2/web/viewer.html?file=' . urlencode($pdfUrl));
        } catch (\Exception $e) {
            abort(403, 'Token không hợp lệ');
        }
    }

    public function mergePdfFilesSecure(Request $request)
    {
        // Cấu hình timeout và memory limit cho xử lý PDF lớn
        $maxExecutionTime = 1800; // 30 phút
        $memoryLimit = '4096M'; // 4GB
        
        // Lưu giá trị hiện tại để có thể khôi phục nếu cần
        $originalTimeLimit = ini_get('max_execution_time');
        $originalMemoryLimit = ini_get('memory_limit');
        
        // Chỉ tăng nếu giá trị hiện tại nhỏ hơn giá trị mới
        if ($originalTimeLimit < $maxExecutionTime || $originalTimeLimit == 0) {
            set_time_limit($maxExecutionTime);
        }
        
        // Chuyển đổi memory limit sang bytes để so sánh
        $currentMemoryBytes = $this->convertToBytes($originalMemoryLimit);
        $newMemoryBytes = $this->convertToBytes($memoryLimit);
        
        if ($currentMemoryBytes < $newMemoryBytes) {
            ini_set('memory_limit', $memoryLimit);
        }
        
        // Log thông tin cấu hình (chỉ trong môi trường development hoặc khi cần debug)
        if (config('app.debug')) {
            \Log::info('PDF Merge: Time limit set to ' . ini_get('max_execution_time') . 's, Memory limit: ' . ini_get('memory_limit'));
        }

        try {
            $token = $request->get('token');

            if (!$token) {
                return response()->json(['error' => 'Thiếu token'], 400);
            }

            $decrypted = Crypt::decryptString($token);
            [$treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);
            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }

            // Lấy danh sách file PDF theo treatmentCode
            $filePaths = $this->get_file_paths($treatmentCode, null);

            if (empty($filePaths) || !$filePaths instanceof \Illuminate\Support\Collection || $filePaths->isEmpty()) {
                return abort(404, 'Không tìm thấy văn bản nào trong hồ sơ này');
            }

            $pdf = new \setasign\Fpdi\Fpdi();

            $tempDir = storage_path('app/temp/');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // $ftp = new \App\Services\FtpService();
            // $ftp->connect();

            foreach ($filePaths as $filePath) {
                $resultUrl = str_replace('\\', '/', $filePath->last_version_url);

                
                $localPath = $tempDir . basename($resultUrl);
                //$ftp->download($resultUrl, $localPath);
                $baseUrl = config('organization.fss_config.baseUrl');
                $url = $baseUrl . $resultUrl;
                $pdfData = $this->pdfFetchService->getPdfAsBase64($url);
                //$localPath = $tempDir . basename($pdfData['url']);
                file_put_contents($localPath, base64_decode($pdfData['base64']));

                $pageCount = $pdf->setSourceFile($localPath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tplIdx = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }

                @unlink($localPath);
            }

            //$ftp->close();

            // 👉 Output PDF trực tiếp ra bộ nhớ (string)
            $output = $pdf->Output('S'); // 'S' => return as string

            return response()->make($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline'
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF Merge Error: ' . $e->getMessage(), [
                'treatment_code' => $treatmentCode ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Link không hợp lệ hoặc đã hết hạn'], 403);
        }
    }

    /**
     * Chuyển đổi memory limit string (ví dụ: "512M", "2G") sang bytes
     * 
     * @param string $value
     * @return int
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    private function get_file_paths($treatmentCode, $ParamDocumentType = null)
    {
        $query = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $treatmentCode);
    
        if (!empty($ParamDocumentType)) {
            $query->whereIn('emr_document.document_type_id', (array) $ParamDocumentType);
        }
    
        $result = $query
            ->orderBy('emr_document_type.num_order', 'ASC')
            ->orderBy('emr_document.document_time', 'ASC')
            ->get();
    
        return $result;
    }
}
