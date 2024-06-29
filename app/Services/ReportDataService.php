<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportDataService
{
    public function buildSqlQueryAndBindingsNdp(Request $request)
    {
        $drug_req_type = $request->input('drug_req_type');
        $prescription_type = $request->input('prescription_type');
        $treatment_code = $request->input('treatment_code');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (strlen($dateFrom) == 10) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        $conditions = [];
        $bindings = [];

        if ($drug_req_type) {
            $conditions[] = "sr.service_req_type_id = :drugReqType";
            $bindings['drugReqType'] = $drug_req_type;
        } else {
            $conditions[] = "sr.service_req_type_id IN (6, 14, 15)";
        }

        if ($prescription_type) {
            $conditions[] = "sr.prescription_type_id = :prescriptionType";
            $bindings['prescriptionType'] = $prescription_type;
        }

        if ($treatment_code) {
            $conditions[] = "sr.tdl_treatment_code = :treatment_code";
            $bindings['treatment_code'] = $treatment_code;
        } else {
            $conditions[] = "sr.intruction_time BETWEEN :formattedDateFrom AND :formattedDateTo";
            $bindings['formattedDateFrom'] = $formattedDateFrom;
            $bindings['formattedDateTo'] = $formattedDateTo;
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT 
                tdl_treatment_code,
                service_req_code,
                tdl_patient_code,
                tdl_patient_name,
                request_room_name,
                request_username,
                icd_code,
                icd_sub_code,
                icd_name,
                icd_text,
                treatment_type_name,
                service_req_type_name,
                service_req_stt_name,
                intruction_time, 
                prescription_type_id,
                SUM(drug_count) AS drug_count,
                SUM(mety_count) AS mety_count
            FROM
            (
                SELECT
                    sr.tdl_treatment_code,
                    sr.service_req_code,
                    sr.tdl_patient_code,
                    sr.tdl_patient_name,
                    br.bed_room_name || ' ' || rr.execute_room_name AS request_room_name,
                    sr.request_username,
                    sr.icd_code,
                    sr.icd_sub_code,
                    sr.icd_name,
                    sr.icd_text,
                    tt.treatment_type_name,
                    srt.service_req_type_name,
                    srst.service_req_stt_name,
                    sr.intruction_time, 
                    sr.prescription_type_id,
                    COUNT(DISTINCT srv.tdl_service_code) AS drug_count,
                    0 AS mety_count
                FROM 
                    his_service_req sr
                LEFT JOIN 
                    his_execute_room rr ON rr.room_id = sr.request_room_id
                LEFT JOIN 
                    his_bed_room br ON br.room_id = sr.request_room_id
                JOIN 
                    his_service_req_stt srst ON srst.id = sr.service_req_stt_id
                JOIN 
                    his_sere_serv srv ON srv.service_req_id = sr.id
                JOIN 
                    his_service_req_type srt ON srt.id = sr.service_req_type_id
                JOIN 
                    his_treatment_type tt ON tt.id = sr.treatment_type_id
                WHERE 
                    sr.is_delete = 0
                    AND srv.is_delete = 0
                    AND srv.is_no_execute IS NULL
                    AND srv.is_expend IS NULL
                    AND srv.tdl_service_type_id = 6
                    AND $whereClause
                GROUP BY 
                    sr.tdl_treatment_code,
                    sr.service_req_code,
                    sr.tdl_patient_code,
                    sr.tdl_patient_name,
                    br.bed_room_name,
                    rr.execute_room_name,
                    sr.request_username,
                    sr.icd_code,
                    sr.icd_sub_code,
                    sr.icd_name,
                    sr.icd_text,
                    tt.treatment_type_name,
                    srt.service_req_type_name,
                    srst.service_req_stt_name,
                    sr.intruction_time,
                    sr.prescription_type_id
                
                UNION 
                
                SELECT
                    sr.tdl_treatment_code,
                    sr.service_req_code,
                    sr.tdl_patient_code,
                    sr.tdl_patient_name,
                    br.bed_room_name || ' ' || rr.execute_room_name AS request_room_name,
                    sr.request_username,
                    sr.icd_code,
                    sr.icd_sub_code,
                    sr.icd_name,
                    sr.icd_text,
                    tt.treatment_type_name,
                    srt.service_req_type_name,
                    srst.service_req_stt_name,
                    sr.intruction_time, 
                    sr.prescription_type_id,
                    0 AS drug_count,
                    COUNT(srm.id) AS mety_count
                FROM 
                    his_service_req sr
                LEFT JOIN 
                    his_execute_room rr ON rr.room_id = sr.request_room_id
                LEFT JOIN 
                    his_bed_room br ON br.room_id = sr.request_room_id
                JOIN 
                    his_service_req_stt srst ON srst.id = sr.service_req_stt_id
                JOIN 
                    his_service_req_mety srm ON srm.service_req_id = sr.id
                JOIN 
                    his_service_req_type srt ON srt.id = sr.service_req_type_id
                JOIN 
                    his_treatment_type tt ON tt.id = sr.treatment_type_id
                WHERE 
                    sr.is_delete = 0
                    AND $whereClause
                GROUP BY 
                    sr.tdl_treatment_code,
                    sr.service_req_code,
                    sr.tdl_patient_code,
                    sr.tdl_patient_name,
                    br.bed_room_name,
                    rr.execute_room_name,
                    sr.request_username,
                    sr.icd_code,
                    sr.icd_sub_code,
                    sr.icd_name,
                    sr.icd_text,
                    tt.treatment_type_name,
                    srt.service_req_type_name,
                    srst.service_req_stt_name,
                    sr.intruction_time,
                    sr.prescription_type_id
            ) t
            GROUP BY
                tdl_treatment_code,
                service_req_code,
                tdl_patient_code,
                tdl_patient_name,
                request_room_name,
                request_username,
                icd_code,
                icd_sub_code,
                icd_name,
                icd_text,
                treatment_type_name,
                service_req_type_name,
                service_req_stt_name,
                intruction_time,
                prescription_type_id";

        return [$sql, $bindings];
    }

    public function buildSqlQueryAndBindingsPa(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (strlen($dateFrom) == 10) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        $conditions = [];
        $bindings = [];

        $conditions[] = "transaction_time BETWEEN :formattedDateFrom AND :formattedDateTo";
        $bindings['formattedDateFrom'] = $formattedDateFrom;
        $bindings['formattedDateTo'] = $formattedDateTo;
 
        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT 
                his_transaction.tdl_treatment_code, 
                his_transaction.tdl_patient_code,
                his_transaction.tdl_patient_name, 
                his_transaction.tdl_patient_dob,
                his_transaction.transaction_code,
                his_transaction.transaction_time,
                his_transaction.tdl_patient_address, 
                transaction_time,
                cashier_username, 
                amount, 
                transaction_type_name, 
                pay_form_name, 
                department_name
            FROM 
                his_transaction
            JOIN 
                his_transaction_type ON his_transaction_type.id = his_transaction.transaction_type_id
            JOIN 
                his_pay_form ON his_pay_form.id = his_transaction.pay_form_id
            JOIN 
                his_treatment ON his_treatment.id = his_transaction.treatment_id
            JOIN 
                his_department ON his_department.id = his_treatment.last_department_id
            WHERE
                his_transaction.is_cancel IS NULL
                AND his_transaction.is_delete = 0
                AND $whereClause";

        return [$sql, $bindings];
    }
}