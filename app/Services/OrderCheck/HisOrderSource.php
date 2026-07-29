<?php

namespace App\Services\OrderCheck;

use Illuminate\Support\Facades\DB;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;

class HisOrderSource
{
    protected $conn;
    protected $excludeTreatmentTypeIds;
    protected $deptMap;
    protected $typeMap;

    public function __construct()
    {
        $this->conn = config('order_check.his_connection');
        $ex = config('order_check.exclude_treatment_type_ids');
        $this->excludeTreatmentTypeIds = $ex === '' ? [] : explode(',', $ex);
    }

    public function fetchServiceRequests($lastModifyTime, $lastId, $limit)
    {
        $q = DB::connection($this->conn)
            ->table('his_service_req as sr')
            ->leftJoin('his_treatment as t', 'sr.treatment_id', '=', 't.id')
            ->leftJoin('his_employee as e', 'sr.execute_loginname', '=', 'e.loginname')
            // Join thu hai de lay CCHN cua bac si CHI DINH; alias e da danh cho nguoi thuc hien.
            ->leftJoin('his_employee as re', 'sr.request_loginname', '=', 're.loginname')
            // Ma CSKCB de doi chieu danh muc theo co so; t la his_treatment da join san.
            ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
            ->where('sr.is_delete', 0)
            ->where('sr.modify_time', '>', $lastModifyTime)
            ->orderBy('sr.modify_time')
            ->orderBy('sr.id')
            ->limit($limit)
            ->selectRaw('/*+ INDEX(sr (MODIFY_TIME)) */
                sr.id, sr.service_req_code, sr.service_req_type_id, sr.treatment_id, sr.intruction_time,
                sr.request_department_id, sr.execute_department_id, sr.request_loginname, sr.request_username,
                sr.icd_code, sr.icd_name, sr.create_time, sr.modify_time,
                sr.icd_sub_code, sr.traditional_icd_code, sr.traditional_icd_sub_code,
                sr.tdl_treatment_code, sr.tdl_patient_code, sr.tdl_patient_name,
                t.in_time as in_time, t.out_time as out_time,
                sr.execute_loginname, sr.execute_username, e.diploma as execute_diploma,
                re.diploma as request_diploma, br.hein_medi_org_code as ma_cskcb');

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
            ->table('his_sere_serv as ss')
            // Ma BHXH nam tren danh muc dich vu HIS, khong nam tren dong y lenh.
            ->leftJoin('his_service as sv', 'sv.id', '=', 'ss.service_id')
            // Ma BHYT cua THUOC nam o danh muc thuoc, khong nam o danh muc dich vu: cot
            // sv.hein_service_bhyt_code chi duoc duy tri cho dich vu ky thuat.
            ->leftJoin('his_medicine as md', 'md.id', '=', 'ss.medicine_id')
            ->leftJoin('his_medicine_type as mdt', 'mdt.id', '=', 'md.medicine_type_id')
            ->where('ss.is_delete', 0)
            ->whereIn('ss.service_req_id', $reqIds)
            ->selectRaw('ss.id, ss.service_req_id, ss.tdl_service_code, ss.tdl_service_name,
                ss.execute_time, ss.tdl_intruction_time, ss.patient_type_id,
                sv.hein_service_bhyt_code, sv.hein_service_bhyt_name, sv.service_type_id
                , mdt.active_ingr_bhyt_code')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $s = new OrderService();
            $s->sereServId = (int) $r->id;
            $s->serviceCode = $r->tdl_service_code;
            $s->serviceName = $r->tdl_service_name;
            $s->executeTime = (int) $r->execute_time;
            $s->tdlIntructionTime = (int) $r->tdl_intruction_time;
            // Doi tuong cua RIENG dong nay - khong lay tu phieu hay ho so.
            $s->patientTypeId = $r->patient_type_id !== null ? (int) $r->patient_type_id : null;
            // Thuoc lay ma hoat chat; vat tu va DVKT khong join ra duoc danh muc thuoc nen
            // roi ve ma dich vu nhu cu.
            $s->bhytCode = \App\Services\OrderCheck\Support\MaBhytDong::cua(
                $r->active_ingr_bhyt_code,
                $r->hein_service_bhyt_code
            );
            $s->bhytName = $r->hein_service_bhyt_name;
            // Quyet dinh quy tac doi chieu voi bang danh muc nao: 6 Thuoc, 7 Vat tu, con
            // lai la DVKT.
            $s->serviceTypeId = $r->service_type_id !== null ? (int) $r->service_type_id : null;
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

    /**
     * Map id => thông tin đợt (treatment_code, bệnh nhân, khoa, mã CSKCB) cho ngữ cảnh vi phạm.
     * Join his_branch giong fetchServiceRequests() de dong bo cach lay ma_cskcb.
     */
    public function fetchTreatmentInfo(array $treatmentIds)
    {
        if (empty($treatmentIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_treatment as t')
            ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
            ->whereIn('t.id', $treatmentIds)
            ->selectRaw('t.id, t.treatment_code, t.tdl_patient_code, t.tdl_patient_name, t.last_department_id, br.hein_medi_org_code as ma_cskcb')
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
            // Ma CSKCB de gan vao vi pham, giong cach lam cua fetchServiceRequests().
            ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
            ->where('ss.is_delete', 0)
            ->where('ss.id', '>', $lastId)
            ->orderBy('ss.id')->limit($limit)
            ->selectRaw('ss.id, ss.create_time, ss.service_req_id, ss.tdl_treatment_id, ss.tdl_service_code, ss.tdl_service_name,
                t.treatment_code, t.tdl_patient_code, t.tdl_patient_name,
                t.tdl_patient_gender_id, t.tdl_patient_dob, br.hein_medi_org_code as ma_cskcb')
            ->get();
    }

    /** Map service_req_id => thông tin phiếu (code, loại, khoa thực hiện) — tra batched theo IN. */
    public function fetchServiceReqInfoByIds(array $reqIds)
    {
        if (empty($reqIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_service_req')
            ->whereIn('id', $reqIds)
            ->selectRaw('id, service_req_code, service_req_type_id, execute_department_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->id] = $r;
        }
        return $map;
    }

    /** [code, name] của khoa theo id (cache 1 lần/run); [null, null] nếu không có. */
    public function departmentInfo($id)
    {
        if ($id === null) {
            return [null, null];
        }
        if ($this->deptMap === null) {
            $this->deptMap = [];
            foreach (DB::connection($this->conn)->table('his_department')->selectRaw('id, department_code, department_name')->get() as $d) {
                $this->deptMap[(int) $d->id] = [$d->department_code, $d->department_name];
            }
        }
        return isset($this->deptMap[(int) $id]) ? $this->deptMap[(int) $id] : [null, null];
    }

    /** Tên loại phiếu chỉ định theo id (cache 1 lần/run). */
    public function serviceReqTypeName($id)
    {
        if ($id === null) {
            return null;
        }
        if ($this->typeMap === null) {
            $this->typeMap = [];
            foreach (DB::connection($this->conn)->table('his_service_req_type')->selectRaw('id, service_req_type_name')->get() as $t) {
                $this->typeMap[(int) $t->id] = $t->service_req_type_name;
            }
        }
        return isset($this->typeMap[(int) $id]) ? $this->typeMap[(int) $id] : null;
    }

    public function buildContext($row, array $services = [])
    {
        $c = new OrderContext();
        $c->serviceReqId = (int) $row->id;
        $c->serviceReqCode = $row->service_req_code;
        $c->serviceReqTypeId = $row->service_req_type_id !== null ? (int) $row->service_req_type_id : null;
        $c->treatmentId = (int) $row->treatment_id;
        $c->treatmentCode = $row->tdl_treatment_code;
        $c->patientCode = $row->tdl_patient_code;
        $c->patientName = $row->tdl_patient_name;
        // Khoa thực hiện (execute_department_id), không phải khoa chỉ định
        $c->departmentId = $row->execute_department_id !== null ? (int) $row->execute_department_id : null;
        $c->doctorLoginname = $row->request_loginname;
        $c->doctorUsername = $row->request_username;
        $c->executeLoginname = $row->execute_loginname;
        $c->executeUsername = $row->execute_username;
        $c->executeDiploma = $row->execute_diploma;
        $c->intructionTime = (int) $row->intruction_time;
        $c->inTime = (int) $row->in_time;
        $c->outTime = (int) $row->out_time;
        $c->icdCode = $row->icd_code;
        $c->icdSubCode = $row->icd_sub_code;
        $c->traditionalIcdCode = $row->traditional_icd_code;
        $c->traditionalIcdSubCode = $row->traditional_icd_sub_code;
        $c->requestDiploma = $row->request_diploma;
        $c->maCskcb = $row->ma_cskcb;
        $c->services = $services;
        return $c;
    }
}
