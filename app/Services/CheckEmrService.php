<?php

namespace App\Services;
use DB;

class CheckEmrService
{
    // Lấy các thông báo (messages)
    public function getMessages()
    {
        return [
            'emr-check-bangke-signer' => [
                'check' => '<h4>Bảng kê thanh toán: Chữ ký của bệnh nhân</h4>',
                'error' => '<label class="alert alert-danger">Bảng kê chưa có chữ ký của bệnh nhân</label>',
                'success' => '<label class="alert alert-success">Bảng kê đã có chữ ký của bệnh nhân</label>',
            ],
            'emr-check-bangke' => [
                'check' => '<h4>Bảng kê thanh toán:</h4>',
                'error' => '<label class="alert alert-danger">Chưa tạo bảng kê</label>',
                'success' => '<label class="alert alert-success">Đã tạo bảng kê</label>',
            ],
            'emr-check-accountant' => [
                'check' => '<h4>Viện phí:</h4>',
                'error' => '<label class="alert alert-danger">Còn nợ viện phí: <strong>{amount} VNĐ</strong></label>',
                'success' => '<label class="alert alert-success">Không nợ viện phí</label>',
            ],
            'no_permission' => [
                'error' => '<div class="alert alert-danger"><strong>Bạn chưa được phân quyền để kiểm tra hồ sơ</strong></div>',
            ]
        ];
    }

    // Lấy thông tin treatment từ bảng his_treatment
    public function getTreatmentDetails($treatment_code)
    {
        return DB::connection('HISPro')
            ->table('his_treatment as tm')
            ->join('his_treatment_type as tt', 'tt.id', '=', 'tm.tdl_treatment_type_id')
            ->join('his_department as last_department', 'last_department.id', '=', 'tm.last_department_id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'tm.tdl_patient_type_id')
            ->where('tm.treatment_code', $treatment_code)
            ->select('treatment_code', 'tdl_patient_name', 'tdl_patient_dob', 'tdl_patient_address',
                'tdl_patient_mobile', 'tdl_patient_phone', 'tdl_patient_relative_mobile',
                'tdl_patient_relative_phone', 'treatment_type_name',
                'last_department.department_name as last_department',
                'pt.patient_type_name',
                'in_time', 'out_time')
            ->get();
    }

    // Lấy thông tin đơn thuốc ngoại trú
    public function getMedicineOutpatientDetails($treatment_code)
    {
        return DB::connection('HISPro')
            ->table('his_sere_serv as ss')
            ->join('his_service_req as sr', 'sr.id', '=', 'ss.service_req_id')
            ->join('his_service_req_stt as srs', 'srs.id', '=', 'sr.service_req_stt_id')
            ->join('his_service_unit as su', 'su.id', '=', 'ss.tdl_service_unit_id')
            ->where('ss.tdl_treatment_code', $treatment_code)
            ->where('ss.is_delete', 0)
            ->where('service_req_type_id', 6)
            ->select('tdl_service_code',
                'tdl_service_name',
                'amount',
                'service_unit_name',
                'service_req_stt_name',
                'tdl_medicine_concentra'
            )
            ->get();
    }

    // Gộp kiểm tra bảng kê và kiểm tra chữ ký
    public function checkBangKeAndSigner($treatment_code)
    {
        $messages = $this->getMessages();
        $html = $messages['emr-check-bangke']['check'];

        // Kiểm tra xem đã tạo bảng kê hay chưa
        $documentExists = DB::connection('EMR_RS')
            ->table('emr_document')
            ->where('treatment_code', $treatment_code)
            ->where('document_type_id', 28)
            ->where('is_delete', 0)
            ->exists();

        // Nếu chưa tạo bảng kê
        if (!$documentExists) {
            return $html . $messages['emr-check-bangke']['error'];
        }

        // Nếu đã tạo bảng kê, hiển thị thông báo thành công
        $html .= $messages['emr-check-bangke']['success'];

        // Kiểm tra tiếp chữ ký nếu có quyền 'emr-check-bangke-signer'
        if (auth()->user()->can('emr-check-bangke-signer')) {
            $html .= $messages['emr-check-bangke-signer']['check'];

            // Kiểm tra xem bảng kê có chữ ký của bệnh nhân hay chưa
            $hasSignature = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 28)
                ->where('is_delete', 0)
                ->where('signers', 'NOT LIKE', '%#@!@#%')
                ->exists();

            if ($hasSignature) {
                $html .= $messages['emr-check-bangke-signer']['error'];
            } else {
                $html .= $messages['emr-check-bangke-signer']['success'];
            }
        }

        return $html;
    }

    // Nghiệp vụ kiểm tra 'emr-check-accountant'
    public function checkAccountant($treatment_code)
    {
        $messages = $this->getMessages();
        $html = $messages['emr-check-accountant']['check'];

        $treatment = DB::connection('HISPro')
            ->table('his_treatment')
            ->select('his_treatment.id')
            ->where('treatment_code', $treatment_code)
            ->first();

        $sere_serv_total = DB::connection('HISPro')
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

        $can_thanh_toan = floor($sere_serv_total->total_patient_price - 
        ($transactions->tam_ung - $transactions->hoan_ung + $transactions->da_thanh_toan));

        if ($can_thanh_toan > 0) {
            $html .= str_replace('{amount}', number_format($can_thanh_toan), $messages['emr-check-accountant']['error']);
            return $html;
        }

        return $html . $messages['emr-check-accountant']['success'];
    }

}