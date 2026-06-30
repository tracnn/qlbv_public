<?php

namespace App\Services\OrderCheck;

use Illuminate\Support\Facades\DB;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;

class HisOrderSource
{
    protected $conn;
    protected $excludeTreatmentTypeIds;

    public function __construct()
    {
        $this->conn = config('order_check.his_connection');
        $ex = config('order_check.exclude_treatment_type_ids');
        $this->excludeTreatmentTypeIds = $ex === '' ? [] : explode(',', $ex);
    }

    public function fetchServiceRequests($lastCreateTime, $lastId, $limit)
    {
        $q = DB::connection($this->conn)
            ->table('his_service_req as sr')
            ->leftJoin('his_treatment as t', 'sr.treatment_id', '=', 't.id')
            ->leftJoin('his_employee as e', 'sr.request_loginname', '=', 'e.loginname')
            ->where('sr.is_delete', 0)
            ->where('sr.id', '>', $lastId)
            ->orderBy('sr.id')
            ->limit($limit)
            ->selectRaw('sr.id, sr.service_req_code, sr.treatment_id, sr.intruction_time,
                sr.request_department_id, sr.request_loginname, sr.request_username,
                sr.icd_code, sr.icd_name, sr.create_time,
                sr.tdl_treatment_code, sr.tdl_patient_code, sr.tdl_patient_name,
                t.in_time as in_time, t.out_time as out_time,
                e.diploma as diploma');

        if (!empty($this->excludeTreatmentTypeIds)) {
            $q->whereNotIn('t.tdl_treatment_type_id', $this->excludeTreatmentTypeIds);
        }

        return $q->get();
    }

    public function fetchServicesByReqIds(array $reqIds)
    {
        if (empty($reqIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->whereIn('service_req_id', $reqIds)
            ->selectRaw('id, service_req_id, tdl_service_code, tdl_service_name, execute_time, tdl_intruction_time')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $s = new OrderService();
            $s->sereServId = (int) $r->id;
            $s->serviceCode = $r->tdl_service_code;
            $s->serviceName = $r->tdl_service_name;
            $s->executeTime = (int) $r->execute_time;
            $s->tdlIntructionTime = (int) $r->tdl_intruction_time;
            $map[(int) $r->service_req_id][] = $s;
        }
        return $map;
    }

    /**
     * Lấy lô tương tác thuốc HIS đã phát hiện, theo watermark (create_time, id).
     * Mỗi dòng đã gắn treatment_id, bác sĩ, ICD, cặp thuốc, mức độ.
     */
    public function fetchInteractions($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_medicine_interactive')
            ->where('is_delete', 0)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($limit)
            ->selectRaw('id, create_time, treatment_id, request_loginname,
                request_department_id, icd_code, icd_name,
                medicine_type_id1, medicine_type_id2, interactive_grade_id')
            ->get();
    }

    /** Lô dòng dịch vụ mới (his_sere_serv) theo watermark — để biết đợt nào vừa phát sinh. */
    public function fetchSereServBatch($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->where('id', '>', $lastId)
            ->orderBy('id')->limit($limit)
            ->selectRaw('id, create_time, tdl_treatment_id')
            ->get();
    }

    /** Toàn bộ dịch vụ đang hoạt động của 1 đợt điều trị. */
    public function fetchTreatmentServices($treatmentId)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->where('tdl_treatment_id', $treatmentId)
            ->selectRaw('id, service_id, tdl_service_code, tdl_service_name')
            ->get();
    }

    /** Lô dòng thuốc mới (his_exp_mest_medicine) theo watermark. */
    public function fetchExpMestBatch($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_exp_mest_medicine')
            ->where('is_delete', 0)
            ->where('id', '>', $lastId)
            ->orderBy('id')->limit($limit)
            ->selectRaw('id, create_time, tdl_treatment_id, medicine_id, tdl_medicine_type_id,
                amount, day_count, morning, noon, afternoon, evening')
            ->get();
    }

    /** Toàn bộ thuốc đang hoạt động của 1 đợt, kèm hoạt chất từ his_medicine. */
    public function fetchTreatmentMedicines($treatmentId)
    {
        return DB::connection($this->conn)
            ->table('his_exp_mest_medicine as em')
            ->leftJoin('his_medicine as m', 'em.medicine_id', '=', 'm.id')
            ->where('em.is_delete', 0)
            ->where('em.tdl_treatment_id', $treatmentId)
            ->selectRaw('em.id, em.medicine_id, em.tdl_medicine_type_id,
                m.active_ingr_bhyt_code as active_ingr_code, m.active_ingr_bhyt_name as active_ingr_name')
            ->get();
    }

    /** Map id => thông tin đợt (treatment_code, bệnh nhân, khoa) cho ngữ cảnh vi phạm. */
    public function fetchTreatmentInfo(array $treatmentIds)
    {
        if (empty($treatmentIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_treatment')
            ->whereIn('id', $treatmentIds)
            ->selectRaw('id, treatment_code, tdl_patient_code, tdl_patient_name, last_department_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->id] = $r;
        }
        return $map;
    }

    /**
     * Lô dòng dịch vụ mới kèm mã DV + giới tính/ngày sinh BN (join treatment) — cho luật giới tính/tuổi.
     */
    public function fetchSereServWithPatient($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv as ss')
            ->leftJoin('his_treatment as t', 'ss.tdl_treatment_id', '=', 't.id')
            ->where('ss.is_delete', 0)
            ->where('ss.id', '>', $lastId)
            ->orderBy('ss.id')->limit($limit)
            ->selectRaw('ss.id, ss.create_time, ss.tdl_treatment_id, ss.tdl_service_code, ss.tdl_service_name,
                t.treatment_code, t.tdl_patient_code, t.tdl_patient_name, t.last_department_id,
                t.tdl_patient_gender_id, t.tdl_patient_dob')
            ->get();
    }

    public function buildContext($row, array $services = [])
    {
        $c = new OrderContext();
        $c->serviceReqId = (int) $row->id;
        $c->serviceReqCode = $row->service_req_code;
        $c->treatmentId = (int) $row->treatment_id;
        $c->treatmentCode = $row->tdl_treatment_code;
        $c->patientCode = $row->tdl_patient_code;
        $c->patientName = $row->tdl_patient_name;
        $c->departmentId = $row->request_department_id !== null ? (int) $row->request_department_id : null;
        $c->doctorLoginname = $row->request_loginname;
        $c->doctorUsername = $row->request_username;
        $c->doctorDiploma = $row->diploma;
        $c->intructionTime = (int) $row->intruction_time;
        $c->inTime = (int) $row->in_time;
        $c->outTime = (int) $row->out_time;
        $c->icdCode = $row->icd_code;
        $c->services = $services;
        return $c;
    }
}
