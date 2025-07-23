<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Crypt;
use App\Patient;
use App\Vaccination;
use Datetime\Datetime;

class PatientController extends Controller
{
    public function viewGuide(Request $request)
    {
        $param_code = '';
        $param_phone = '';

        $histories = collect();
        try {

            $token = $request->get('token');


            if (!$token) {
                return view('patient.view-guide', 
                compact('histories', 'param_phone', 'param_code'));
            }

            $decrypted = Crypt::decryptString($token);
            [$param_code, $param_phone, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }

            $treatment_code = strlen($param_code) < 12 ? str_pad($param_code, 12, '0', STR_PAD_LEFT) : $param_code;
            $inTimeLimit = now()->subMonths(12)->format('YmdHis');
            

            if ($param_code && $param_phone) {
                $histories = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
                ->join('his_patient_type', 'his_treatment.tdl_patient_type_id', '=', 'his_patient_type.id')
                ->join('his_treatment_type', 'his_treatment.tdl_treatment_type_id', '=', 'his_treatment_type.id')
                ->leftJoin('his_treatment_result', 'his_treatment.treatment_result_id', '=' ,'his_treatment_result.id')
                ->leftJoin('his_treatment_end_type', 'his_treatment.treatment_end_type_id', '=' ,'his_treatment_end_type.id')
                ->select('his_treatment.id','his_treatment.treatment_code','his_treatment.tdl_patient_name',
                    'his_treatment.tdl_patient_dob','his_treatment.tdl_patient_gender_name','his_treatment.tdl_patient_type_id',
                    'his_patient_type.patient_type_name','his_treatment.tdl_patient_address','his_treatment.tdl_patient_phone',
                    'his_treatment_type.treatment_type_name','his_treatment.in_time','his_treatment.out_time',
                    'his_treatment.tdl_patient_career_name','his_treatment_result.treatment_result_name',
                    'his_treatment_end_type.treatment_end_type_name',
                    'his_treatment.tdl_patient_cmnd_number','his_treatment.tdl_patient_cccd_number',
                    'his_treatment.tdl_patient_passport_number','his_treatment.tdl_hein_card_number',
                    'his_treatment.icd_name', 'his_treatment.icd_text'
                )
                ->where(function($q) use ($treatment_code, $param_code){
                    $q->where('his_treatment.treatment_code', $treatment_code)
                    ->orWhere('his_treatment.tdl_patient_code', $param_code)
                    ->orWhere('his_treatment.tdl_patient_cmnd_number', $param_code)
                    ->orWhere('his_treatment.tdl_patient_cccd_number', $param_code)
                    ->orWhere('his_treatment.tdl_patient_passport_number', $param_code);
                })
                ->where(function($q) use ($param_phone){
                    $q->where('his_patient.phone', $param_phone)
                    ->orWhere('his_patient.mobile', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_mobile', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_relative_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_relative_mobile', $param_phone);;
                })
                //->where('his_treatment.in_time', '>=', $inTimeLimit)
                ->orderBy('in_time','desc')
                ->paginate(3);
            }
            //return $histories;
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return view('patient.view-guide', 
            compact('histories', 'param_phone', 'param_code'));
    }

    public function viewGuideContent(Request $request)
    {
        $treatment_code = null;
        $phone = null;
        $treatment = null;
        $service_req = null;
        $emr_document = null;
        $service_kham = null;
        $sere_serv_cdha = null;
        $service_req_notStarted = null;
        $countServiceReqNotStartByRoom = null;
        $sere_serv_chiphi = null;
        $tracuuhoadon = null;
        $barcode = null;
        $sere_serv_total = null;
        $transactions = null;
        $patient = null;
        $vaccinations = null;

        try {

            $token = $request->get('token');
            // Giải mã token
            if (!$token) {
                return view('patient.view-guide-content',
                    compact('treatment_code','phone','treatment','service_req','emr_document','service_kham','sere_serv_cdha',
                        'service_req_notStarted', 'countServiceReqNotStartByRoom','sere_serv_chiphi','tracuuhoadon','barcode',
                        'sere_serv_total', 'transactions', 'vaccinations')
                );                
            }

            $decrypted = \Crypt::decryptString($token);
            [$treatment_code, $phone, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }            

            if (!$treatment_code || !$phone) {
                return abort(403, 'Link không hợp lệ hoặc thiếu dữ liệu');
            }     

            if ($treatment_code && $phone) {
                $treatment = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
                ->join('his_patient_type', 'his_treatment.tdl_patient_type_id', '=', 'his_patient_type.id')
                ->join('his_treatment_type', 'his_treatment.tdl_treatment_type_id', '=', 'his_treatment_type.id')
                ->leftJoin('his_treatment_result', 'his_treatment.treatment_result_id', '=' ,'his_treatment_result.id')
                ->leftJoin('his_treatment_end_type', 'his_treatment.treatment_end_type_id', '=' ,'his_treatment_end_type.id')
                ->select('his_treatment.id','his_treatment.treatment_code','his_treatment.tdl_patient_name',
                    'his_patient.vir_dob_year','his_treatment.tdl_patient_gender_name','his_treatment.tdl_patient_type_id',
                    'his_patient_type.patient_type_name','his_treatment.tdl_patient_address','his_patient.phone',
                    'his_treatment_type.treatment_type_name','his_treatment.in_time','his_treatment.out_time',
                    'his_treatment.tdl_patient_career_name','his_treatment_result.treatment_result_name',
                    'his_treatment_end_type.treatment_end_type_name',
                    'his_treatment.tdl_patient_cmnd_number','his_treatment.tdl_patient_cccd_number',
                    'his_treatment.tdl_patient_passport_number','his_treatment.tdl_hein_card_number',
                    'his_treatment.icd_name', 'his_treatment.icd_text', 'his_treatment.tdl_patient_code',
                    'his_treatment.tdl_treatment_type_id'
                )
                ->where('his_treatment.treatment_code', $treatment_code)
                ->where(function($q) use ($phone){
                    $q->where('his_patient.phone', $phone)
                    ->orWhere('his_patient.mobile', $phone)
                    ->orWhere('his_patient.relative_mobile', $phone)
                    ->orWhere('his_patient.relative_phone', $phone)
                    ->orWhere('his_treatment.tdl_patient_phone', $phone)
                    ->orWhere('his_treatment.tdl_patient_mobile', $phone);
                })
                ->first();
            }

            if (!empty($treatment->id)) {
                $service_req_notStarted = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->join('his_room', 'his_room.id', '=', 'his_execute_room.room_id')
                ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->select('his_service_req_type.service_req_type_name','his_service_req_stt.service_req_stt_name',
                    'his_service_req_stt.service_req_stt_code','his_execute_room.execute_room_name','his_room.address',
                    'his_service_req.num_order','his_service_req.execute_room_id','his_service_req.intruction_time',
                    'his_sere_serv.tdl_service_name'
                )
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_sere_serv.is_delete', 0)
                ->whereNull('his_sere_serv.is_expend')
                ->whereNull('his_sere_serv.is_no_execute')
                ->where('his_service_req.service_req_stt_id', '=', 1)
                ->whereNotIn('his_service_req.service_req_type_id', [6,7,11,14,15,16,17])
                ->orderBy('his_service_req.num_order')
                ->get();

                $service_req = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->join('his_room', 'his_room.id', '=', 'his_execute_room.room_id')
                ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->select('his_service_req_type.service_req_type_name','his_service_req_stt.service_req_stt_name',
                    'his_service_req_stt.service_req_stt_code','his_execute_room.execute_room_name','his_room.address',
                    'his_service_req.num_order','his_service_req.execute_room_id','his_sere_serv.tdl_service_name'
                )
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_service_req.service_req_stt_id', '<>', 1)
                ->whereNotIn('his_service_req.service_req_type_id', [7,11])
                ->orderBy('his_service_req.service_req_code')
                ->get();

                $emr_document = DB::connection('EMR_RS')
                ->table('emr_document')
                ->leftJoin('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
                ->select('emr_document.document_name','emr_document.document_code',
                    'emr_document_type.document_type_name','emr_document.his_code', 'emr_document.treatment_code')
                ->where('emr_document.is_delete', 0)
                ->where('emr_document.treatment_code', $treatment->treatment_code)
                ->where( function ($q) {
                    $q->whereIn('emr_document.document_type_id', config('organization.patient.emr_document_type_result_ids'))
                    ->orWhereNull('emr_document.document_type_id');
                })
                ->where( function ($q) {
                    $q->whereNotIn('emr_document.document_group_id', [28])
                    ->orWhereNull('emr_document.document_group_id');
                })          
                ->get();
                     
                $service_kham = DB::connection('HISPro')
                ->table('his_service_req')
                ->leftJoin('his_dhst', 'his_dhst.id', '=', 'his_service_req.dhst_id')
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_service_req.service_req_type_id', 1)
                ->first();

                $sere_serv_cdha = DB::connection('HISPro')  
                ->table('his_sere_serv')
                ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
                ->select('his_sere_serv.id', 
                    'his_sere_serv.tdl_service_name', 
                    'his_sere_serv.tdl_intruction_time', 
                    'his_service_req.tdl_patient_code'
                )
                ->where('his_sere_serv.is_delete', 0)
                ->where('his_sere_serv.tdl_service_type_id', 3)
                ->where('his_sere_serv.tdl_treatment_id', $treatment->id)
                ->get();

                $sere_serv_chiphi = DB::connection('HISPro')  
                ->table('his_sere_serv')
                ->join('his_service_type', 'his_service_type.id', '=', 'his_sere_serv.tdl_service_type_id')
                ->selectRaw('sum(amount*price) as thanh_tien, sum(amount) as so_luong, service_type_name')
                ->where('his_sere_serv.is_delete', 0)
                ->whereNull('his_sere_serv.is_expend')
                ->whereNull('his_sere_serv.is_no_pay')
                ->whereNull('his_sere_serv.is_no_execute')
                ->where('tdl_treatment_id', $treatment->id)
                ->groupBy('service_type_name')
                ->get();
                //dd($sere_serv_chiphi);
                $tracuuhoadon = DB::connection('HISPro')
                ->table('his_transaction')
                ->select('invoice_sys','treatment_total_price','treatment_hein_price','treatment_patient_price',
                    'einvoice_time','invoice_lookup_code','amount','treatment_bill_amount')
                ->where('treatment_id', $treatment->id)
                ->whereNotNull('einvoice_num_order')
                ->whereNull('is_cancel_einvoice')
                ->whereNull('is_cancel')
                ->get();
                //dd($tracuuhoadon);

                $sere_serv_total =DB::connection('HISPro')
                ->table('his_sere_serv')
                ->where('tdl_treatment_id', $treatment->id)
                ->where('is_delete', 0)
                ->whereNull('is_expend')
                ->whereNull('is_no_pay')
                ->whereNull('is_no_execute')
                ->selectRaw('SUM(vir_total_price) AS total_price, SUM(vir_total_hein_price) AS total_hein_price, 
                    SUM(vir_total_patient_price) AS total_patient_price')
                ->first();

                $transactions = DB::connection('HISPro')
                ->table('his_transaction')
                ->where('treatment_id', $treatment->id)
                ->whereNull('is_cancel')
                ->where('is_delete', 0)
                ->selectRaw('
                    SUM(CASE WHEN transaction_type_id = 1 THEN amount ELSE 0 END) AS tam_ung,
                    SUM(CASE WHEN transaction_type_id = 2 THEN amount ELSE 0 END) AS hoan_ung,
                    SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id IS NULL THEN amount ELSE 0 END) AS da_thanh_toan,
                    SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 2 THEN amount ELSE 0 END) AS tu_nhap,
                    SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 1 THEN amount ELSE 0 END) AS xuat_ban,
                    SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 3 THEN amount ELSE 0 END) AS vitamin_a
                ')
                ->first();

                $patient = Patient::where('code', $treatment->tdl_patient_code)
                ->first();

                if ($patient) {
                    $vaccinations = Vaccination::where('patient_id', $patient->id)
                    ->get();
                }

                $generator = new BarcodeGeneratorPNG();
                $barcode =  base64_encode($generator->getBarcode($treatment_code, $generator::TYPE_CODE_128));

            }            
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return view('patient.view-guide-content',
            compact('treatment_code','phone','treatment','service_req','emr_document','service_kham','sere_serv_cdha',
                'service_req_notStarted', 'countServiceReqNotStartByRoom','sere_serv_chiphi','tracuuhoadon','barcode',
                'sere_serv_total', 'transactions', 'vaccinations')
        );
    }

    /**
     * API: Lấy danh sách ngày có y lệnh (group by ngày)
     */
    public function getListCongkhaiDays(Request $request)
    {
        $treatmentId = $request->query('treatment_id');
        $currentDate = date('Ymd') . '235959';

        $dates = DB::connection('HISPro')
        ->table('his_service_req')
        ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
        ->where('his_service_req.treatment_id', $treatmentId)
        ->where('his_service_req.intruction_date', '<=', $currentDate)
        ->where('his_sere_serv.amount', '>', 0)
        ->where('his_service_req.is_delete', 0)
        ->select('his_service_req.intruction_date as day')
        ->groupBy('his_service_req.intruction_date')
        ->orderBy('his_service_req.intruction_date', 'desc')
        ->pluck('day');

        return response()->json(['days' => $dates]);
    }

    /**
     * API: Lấy chi tiết DVKT & VTYT theo ngày
     */
    public function getListCongkhaiDvkt(Request $request)
    {
        $treatmentId = $request->query('treatment_id');
        $day = $request->query('day');

        $currentDate = date('Ymd') . '235959';

        $details = DB::connection('HISPro')
        ->table('his_service_req as sr')
        ->join('his_sere_serv as s', 'sr.id', '=', 's.service_req_id')
        ->join('his_service_unit as su', 'su.id', '=', 's.tdl_service_unit_id')
        ->join('his_service_type as st', 'st.id', '=', 's.tdl_service_type_id')
        ->where('sr.treatment_id', $treatmentId)
        ->whereNotIn('s.tdl_service_type_id', [6]) // 6: Thuốc
        ->where('sr.intruction_date', $day)
        ->where('sr.intruction_date', '<=', $currentDate)
        ->where('s.amount', '>', 0)
        ->where('s.is_delete', 0)
        ->where('sr.is_delete', 0)
        ->select([
            'st.service_type_name as type',
            's.tdl_service_name as name',
            'su.service_unit_name as unit',
            's.amount as amount',
            's.price as price'
        ])
        ->get();

        return response()->json(['details' => $details]);
    }

    /**
     * API: Lấy chi tiết Thuốc theo ngày
     */
    public function getListCongkhaiThuoc(Request $request)
    {
        $treatmentId = $request->query('treatment_id');
        $day = $request->query('day');
        $currentDate = date('Ymd') . '235959';

        $details = DB::connection('HISPro')
        ->table('his_service_req as sr')
        ->join('his_sere_serv as s', 's.service_req_id', '=', 'sr.id')
        ->join('his_service_unit as su', 'su.id', '=', 's.tdl_service_unit_id')
        ->leftJoin('his_exp_mest_medicine as t', 't.id', '=', 's.exp_mest_medicine_id')
        ->where('sr.treatment_id', $treatmentId)
        ->whereIn('s.tdl_service_type_id', [6]) // 3: Thuốc
        ->where('sr.intruction_date', $day)
        ->where('sr.intruction_date', '<=', $currentDate)
        ->where('s.amount', '>', 0)
        ->where('s.is_delete', 0)
        ->where('sr.is_delete', 0)
        ->whereNull('s.is_no_execute')
        ->select([
            's.tdl_service_name as name',
            'su.service_unit_name as unit',
            's.amount as amount',
            's.price as price',
            's.tdl_medicine_concentra as concentration',
            's.tdl_active_ingr_bhyt_name as form',
            't.tutorial'
        ])
        ->get();

        return response()->json(['details' => $details]);
    }

    public function encryptToken(Request $request)
    {
        $treatmentCode = $request->get('treatment_code');
        $phone = $request->get('phone');


        if (!$treatmentCode || !$phone) {
            return response()->json(['error' => 'Thiếu thông tin'], 400);
        }

        $createdAt = now()->timestamp; // Thời điểm tạo token
        $expiresIn = 7200; // thời gian sống (giây) – ví dụ: 5 phút

        $isExist = DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
        ->where('his_treatment.treatment_code', $treatmentCode)
        ->where(function($q) use ($phone){
            $q->where('his_patient.phone', $phone)
            ->orWhere('his_patient.mobile', $phone)
            ->orWhere('his_patient.relative_mobile', $phone)
            ->orWhere('his_patient.relative_phone', $phone)
            ->orWhere('his_treatment.tdl_patient_phone', $phone)
            ->orWhere('his_treatment.tdl_patient_mobile', $phone);
        })
        ->exists();

        $token = Crypt::encryptString("{$treatmentCode}|{$phone}|{$createdAt}|{$expiresIn}");

        return response()->json(['token' => $token, 'isExist' => $isExist]);
    }

    public function encryptTokenGeneral(Request $request)
    {
        $code  = $request->get('code');
        $phone = $request->get('phone');

        if (!$code  || !$phone) {
            return response()->json(['error' => 'Thiếu thông tin'], 400);
        }

        $isExist = DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
        ->where(function($q) use ($code, $phone){
            $q->where('his_treatment.treatment_code', $code)
            ->orWhere('his_treatment.tdl_patient_code', $code)
            ->orWhere('his_treatment.tdl_patient_cmnd_number', $code)
            ->orWhere('his_treatment.tdl_patient_cccd_number', $code)
            ->orWhere('his_treatment.tdl_patient_passport_number', $code);
        })
        ->where(function($q) use ($phone){
            $q->where('his_patient.phone', $phone)
            ->orWhere('his_patient.mobile', $phone)
            ->orWhere('his_treatment.tdl_patient_phone', $phone)
            ->orWhere('his_treatment.tdl_patient_mobile', $phone)
            ->orWhere('his_treatment.tdl_patient_relative_phone', $phone)
            ->orWhere('his_treatment.tdl_patient_relative_mobile', $phone);;
        })
        ->exists();

        $createdAt = now()->timestamp; // Thời điểm tạo token
        $expiresIn = 7200; // thời gian sống (giây) – ví dụ: 5 phút

        $token = Crypt::encryptString("{$code}|{$phone}|{$createdAt}|{$expiresIn}");

        return response()->json(['token' => $token, 'isExist' => $isExist]);
    }

}
