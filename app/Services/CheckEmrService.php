<?php

namespace App\Services;
use DB;
use App\Models\CheckBHYT\check_hein_card;

class CheckEmrService
{
    // Lấy các thông báo (messages)
    public function getMessages()
    {
        return config('emr_messages');
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
                'pt.patient_type_name', 'tdl_patient_code', 'tdl_hein_card_number',
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
            ->leftJoin('his_exp_mest_medicine as emm', 'emm.id', '=', 'ss.exp_mest_medicine_id')
            ->where('ss.tdl_treatment_code', $treatment_code)
            ->where('ss.is_delete', 0)
            ->where('service_req_type_id', 6)
            ->select('ss.tdl_service_code',
                'ss.tdl_service_name',
                'ss.amount',
                'su.service_unit_name',
                'srs.service_req_stt_name',
                'ss.tdl_medicine_concentra',
                'emm.tutorial'
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

    // emr-check-bbhc-info
    public function checkBbhcInfo($treatment_code)
    {
        $messages = $this->getMessages();
        $html = '';
        
        //1. Kiểm tra BBHC DVKT
        $dvktBbhcExists = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
            ->where('his_service_req.tdl_treatment_code', $treatment_code)
            ->whereIn('execute_room_id', [66, 162, 249, 322])
            ->where('his_service_req.is_delete', 0)
            ->where('his_sere_serv.is_delete', 0)
            ->get();

        if ($dvktBbhcExists->isNotEmpty()) {
            $html = $messages['emr-check-bbhc-dvkt']['check'];
            $dvktDebateExists = DB::connection('HISPro')
                ->table('v_his_debate')
                ->where('treatment_code', $treatment_code)
                ->where('is_delete', 0)
                ->get();

            if ($dvktDebateExists->isNotEmpty()) {
                foreach ($dvktDebateExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->request_content . '</h4>';
                    $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-his']['success'];
                    $textCompare = 'DEBATE_ID:' . $value->id;
                    $documentExists = DB::connection('EMR_RS')
                        ->table('emr_document')
                        ->where('treatment_code', $treatment_code)
                        ->where('document_type_id', 17)
                        ->where('his_code', 'like', '%' . $textCompare . '%') 
                        ->where('is_delete', 0)
                        ->exists();
                    if (!$documentExists) {
                        $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-emr']['error'];
                    } else {
                        $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-emr']['success'];
                        $documentExists = DB::connection('EMR_RS')
                            ->table('emr_document')
                            ->where('treatment_code', $treatment_code)
                            ->where('document_type_id', 17)
                            ->where('his_code', 'like', '%' . $textCompare . '%') 
                            ->where('is_delete', 0)
                            ->whereNotNull('next_signer')
                            ->whereNotNull('un_signers')
                            ->exists();
                        if ($documentExists) {
                            $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-emr-signer']['error'];
                        } else {
                            $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-emr-signer']['success'];
                        }
                    }
                }
            } else {
                foreach ($dvktBbhcExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->tdl_service_name . '</h4>';
                    $html .= $messages['emr-check-bbhc-dvkt']['bbhc-dvkt-his']['error'];
                }
            }
        }    

        //2. Kiểm tra BBHC PTTT
        $ptttBbhcExists = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
            ->where('his_service_req.tdl_treatment_code', $treatment_code)
            ->where('service_req_type_id', 10)
            ->where('his_service_req.is_delete', 0)
            ->where('his_sere_serv.is_delete', 0)
            ->get();

        if ($ptttBbhcExists->isNotEmpty()) {
            $html = $messages['emr-check-bbhc-pttt']['check'];
            $dvktDebateExists = DB::connection('HISPro')
                ->table('v_his_debate')
                ->where('treatment_code', $treatment_code)
                ->where('is_delete', 0)
                ->get();

            if ($dvktDebateExists->isNotEmpty()) {
                foreach ($dvktDebateExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->request_content . '</h4>';
                    $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-his']['success'];
                    $textCompare = 'DEBATE_ID:' . $value->id;
                    $documentExists = DB::connection('EMR_RS')
                        ->table('emr_document')
                        ->where('treatment_code', $treatment_code)
                        ->where('document_type_id', 17)
                        ->where('his_code', 'like', '%' . $textCompare . '%') 
                        ->where('is_delete', 0)
                        ->exists();
                    if (!$documentExists) {
                        $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-emr']['error'];
                    } else {
                        $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-emr']['success'];
                        $documentExists = DB::connection('EMR_RS')
                            ->table('emr_document')
                            ->where('treatment_code', $treatment_code)
                            ->where('document_type_id', 17)
                            ->where('his_code', 'like', '%' . $textCompare . '%') 
                            ->where('is_delete', 0)
                            ->whereNotNull('next_signer')
                            ->whereNotNull('un_signers')
                            ->exists();
                        if ($documentExists) {
                            $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-emr-signer']['error'];
                        } else {
                            $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-emr-signer']['success'];
                        }
                    }
                }
            } else {
                foreach ($ptttBbhcExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->tdl_service_name . '</h4>';
                    $html .= $messages['emr-check-bbhc-pttt']['bbhc-pttt-his']['error'];
                }
            }
        }

        //3. Kiểm tra BBHC thuốc (*)
        $thuocBbhcExists = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_medicine', 'his_medicine.id', '=', 'his_sere_serv.medicine_id')
            ->join('his_medicine_type', 'his_medicine_type.id', '=', 'his_medicine.medicine_type_id')
            ->where('his_sere_serv.tdl_treatment_code', $treatment_code)
            ->whereNotNull('his_medicine_type.is_star_mark')
            ->where('his_sere_serv.is_delete', 0)
            ->groupBy('his_sere_serv.tdl_service_name')
            ->select('his_sere_serv.tdl_service_name')
            ->get();

        if ($thuocBbhcExists->isNotEmpty()) {
            $html = $messages['emr-check-bbhc-thuoc']['check'];
            $dvktDebateExists = DB::connection('HISPro')
                ->table('v_his_debate')
                ->where('treatment_code', $treatment_code)
                ->where('content_type', 2)
                ->where('is_delete', 0)
                ->get();

            if ($dvktDebateExists->isNotEmpty()) {
                foreach ($dvktDebateExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->request_content . '</h4>';
                    $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-his']['success'];
                    $textCompare = 'DEBATE_ID:' . $value->id;
                    $documentExists = DB::connection('EMR_RS')
                        ->table('emr_document')
                        ->where('treatment_code', $treatment_code)
                        ->where('document_type_id', 17)
                        ->where('his_code', 'like', '%' . $textCompare . '%') 
                        ->where('is_delete', 0)
                        ->exists();
                    if (!$documentExists) {
                        $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-emr']['error'];
                    } else {
                        $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-emr']['success'];
                        $documentExists = DB::connection('EMR_RS')
                            ->table('emr_document')
                            ->where('treatment_code', $treatment_code)
                            ->where('document_type_id', 17)
                            ->where('his_code', 'like', '%' . $textCompare . '%') 
                            ->where('is_delete', 0)
                            ->whereNotNull('next_signer')
                            ->whereNotNull('un_signers')
                            ->exists();
                        if ($documentExists) {
                            $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-emr-signer']['error'];
                        } else {
                            $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-emr-signer']['success'];
                        }
                    }
                }
            } else {
                foreach ($thuocBbhcExists as $key => $value) {
                    $html .= '<h4>' . ($key + 1) . '. ' . $value->tdl_service_name . '</h4>';
                    $html .= $messages['emr-check-bbhc-thuoc']['bbhc-thuoc-his']['error'];
                }
            }
        }

        return $html;
    }

    // emr-check-general-info
    public function checkGeneralInfo($treatment_code)
    {
        $messages = $this->getMessages();
        $html = '';

        // Lấy dữ liệu check thẻ BHYT. Nếu có lỗi thì trả về chuổi html
        $theBhyt = check_hein_card::where('ma_lk', $treatment_code)
            ->where(function($query) {
                    $query->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
                    ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
                })
            ->first();

        if ($theBhyt) {
            $msgBhyt = 'Thẻ BHYT:';
            
            // Kiểm tra ma_kiemtra thuộc config('qd130xml.hein_card_invalid.check_code')
            if (in_array($theBhyt->ma_kiemtra, config('qd130xml.hein_card_invalid.check_code'))) {
                $msgBhyt .= ' ' . config('__tech.check_insurance_code')[$theBhyt->ma_kiemtra];
            }
            
            // Kiểm tra ma_tracuu thuộc config('qd130xml.hein_card_invalid.result_code')
            if (in_array($theBhyt->ma_tracuu, config('qd130xml.hein_card_invalid.result_code'))) {
                // Nối thêm thông báo ma_tracuu nếu có
                $msgBhyt .= ' ' . config('__tech.insurance_error_code')[$theBhyt->ma_tracuu];
            }
            
            // Nếu có lỗi, hiển thị thông báo lỗi
            if ($msgBhyt) {
                $html .= '<div style="display: inline-block;"><label class="alert alert-danger">' . $msgBhyt . '</label></div>';
            }
        }

        $treatmentInpatientExists = DB::connection('EMR_RS')
            ->table('emr_treatment')
            ->where('treatment_code', $treatment_code)
            ->where('treatment_type_code', '03')
            ->exists();

        if ($treatmentInpatientExists) {
            $html = $messages['emr-check-general-info']['check'];
            //1. Kiểm tra Vỏ bệnh án nếu Diện điều trị là nội trú
            $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 1)
                ->where('is_delete', 0)
                ->exists();
            if (!$documentExists) {
                $html .= $messages['emr-check-general-info']['vo-benh-an-hanh-chinh']['error'];
            } else {
                $html .= $messages['emr-check-general-info']['vo-benh-an-hanh-chinh']['success'];

                $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 1)
                ->where('is_delete', 0)
                ->whereNotNull('next_signer')
                ->whereNotNull('un_signers')
                ->exists();
                if ($documentExists) {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-hanh-chinh-signer']['error'];
                } else {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-hanh-chinh-signer']['success'];
                }
            }
            
            $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 41)
                ->where('is_delete', 0)
                ->exists();
            if (!$documentExists) {
                $html .= $messages['emr-check-general-info']['vo-benh-an-hoi-benh']['error'];
            } else {
                $html .= $messages['emr-check-general-info']['vo-benh-an-hoi-benh']['success'];

                $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 41)
                ->where('is_delete', 0)
                ->whereNotNull('next_signer')
                ->whereNotNull('un_signers')
                ->exists();
                if ($documentExists) {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-hoi-benh-signer']['error'];
                } else {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-hoi-benh-signer']['success'];
                }
            }

            $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 42)
                ->where('is_delete', 0)
                ->exists();
            if (!$documentExists) {
                $html .= $messages['emr-check-general-info']['vo-benh-an-tong-ket']['error'];
            } else {
                $html .= $messages['emr-check-general-info']['vo-benh-an-tong-ket']['success'];
                $documentExists = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('treatment_code', $treatment_code)
                ->where('document_type_id', 42)
                ->where('is_delete', 0)
                ->whereNotNull('next_signer')
                ->whereNotNull('un_signers')
                ->exists();
                if ($documentExists) {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-tong-ket-signer']['error'];
                } else {
                    $html .= $messages['emr-check-general-info']['vo-benh-an-tong-ket-signer']['success'];
                }
            }
        }

        return $html;
    }

    // emr-check-advnace-info
    public function checkAdvanceInfo($treatment_code)
    {
        $messages = $this->getMessages();
        $html = '';

        $treatmentExits = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('treatment_code', $treatment_code)
            ->whereIn('tdl_treatment_type_id', [3, 4])
            ->exists();
        if ($treatmentExits) {
            $keywords = $messages['inpatient']['keywords'];

            $html .= $messages['inpatient']['check'];

            $examResult = DB::connection('HISPro')
                ->table('his_service_req')
                ->leftJoin('his_dhst', 'his_dhst.id', '=', 'his_service_req.dhst_id')
                ->where('his_service_req.tdl_treatment_code', $treatment_code)
                ->where('his_service_req.service_req_type_id', 1)
                ->where('is_main_exam', 1)
                ->where('his_service_req.is_delete', 0)
                ->select(
                    DB::raw("COALESCE(his_service_req.icd_name, '') || ' ' || 
                             COALESCE(his_service_req.icd_text, '') || ' ' || 
                             COALESCE(his_service_req.hospitalization_reason, '') || ' ' || 
                             COALESCE(his_service_req.pathological_process, '') || ' ' || 
                             COALESCE(his_service_req.pathological_history, '') || ' ' || 
                             COALESCE(his_service_req.full_exam, '') || ' ' || 
                             COALESCE(his_service_req.part_exam, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_circulation, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_respiratory, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_digestion, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_kidney_urology, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_neurological, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_muscle_bone, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_ent, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_ear, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_nose, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_throat, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_stomatology, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eye, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eye_tension_left, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eye_tension_right, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eyesight_left, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eyesight_right, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eyesight_glass_left, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_eyesight_glass_right, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_oend, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_mental, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_obstetric, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_nutrition, '') || ' ' || 
                             COALESCE(his_service_req.part_exam_motion, '') AS clinical_text"),
                    'his_dhst.temperature',
                    'his_dhst.blood_pressure_max',
                    'his_dhst.blood_pressure_min'
                )
                ->first();
            if ($examResult) {
                $html .= $examResult->clinical_text;

                // Chuyển văn bản lâm sàng thành chữ thường để đối chiếu
                $clinicalText = mb_strtolower($examResult->clinical_text, 'UTF-8');
                
                // Mảng để lưu các từ khóa đã khớp
                $matchedKeywords = [];
                // Danh sách từ khóa đã lọc
                $keywords = $messages['inpatient']['keywords'];

                // Kiểm tra từng từ khóa trong văn bản lâm sàng
                foreach ($keywords as $keyword) {
                    if (strpos($clinicalText, mb_strtolower($keyword, 'UTF-8')) !== false) {
                        $matchedKeywords[] = $keyword;
                    }
                }

                // Kiểm tra nếu tìm thấy từ khóa nào trong văn bản
                if (count($matchedKeywords) > 0) {
                    $html .= $messages['inpatient']['success'];
                    $html .= implode(', ', $matchedKeywords);
                } else {
                    $html .= $messages['inpatient']['error'];
                }                
            } else {
                $html .= $messages['inpatient']['no_exam'];
            }
        }

        return $html;
    }
}