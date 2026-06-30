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
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('sr.create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('sr.create_time', '=', $lastCreateTime)
                         ->where('sr.id', '>', $lastId);
                  });
            })
            ->orderBy('sr.create_time')
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
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('create_time', '=', $lastCreateTime)
                         ->where('id', '>', $lastId);
                  });
            })
            ->orderBy('create_time')
            ->orderBy('id')
            ->limit($limit)
            ->selectRaw('id, create_time, treatment_id, request_loginname,
                request_department_id, icd_code, icd_name,
                medicine_type_id1, medicine_type_id2, interactive_grade_id')
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
