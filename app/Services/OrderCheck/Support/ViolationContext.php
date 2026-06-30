<?php

namespace App\Services\OrderCheck\Support;

/**
 * Snapshot ngữ cảnh kèm theo mỗi violation khi ghi DB.
 * Tách khỏi OrderContext để mọi scanner (service_req, interaction-log, ...) dùng chung.
 */
class ViolationContext
{
    public $treatmentId;
    public $treatmentCode;
    public $patientCode;
    public $patientName;
    public $doctorLoginname;
    public $doctorUsername;
    public $departmentId;

    public static function make(array $a)
    {
        $c = new self();
        $c->treatmentId = isset($a['treatment_id']) ? $a['treatment_id'] : null;
        $c->treatmentCode = isset($a['treatment_code']) ? $a['treatment_code'] : null;
        $c->patientCode = isset($a['patient_code']) ? $a['patient_code'] : null;
        $c->patientName = isset($a['patient_name']) ? $a['patient_name'] : null;
        $c->doctorLoginname = isset($a['doctor_loginname']) ? $a['doctor_loginname'] : null;
        $c->doctorUsername = isset($a['doctor_username']) ? $a['doctor_username'] : null;
        $c->departmentId = isset($a['department_id']) ? $a['department_id'] : null;
        return $c;
    }

    public static function fromOrderContext(OrderContext $o)
    {
        return self::make([
            'treatment_id' => $o->treatmentId,
            'treatment_code' => $o->treatmentCode,
            'patient_code' => $o->patientCode,
            'patient_name' => $o->patientName,
            'doctor_loginname' => $o->doctorLoginname,
            'doctor_username' => $o->doctorUsername,
            'department_id' => $o->departmentId,
        ]);
    }
}
