<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Chon PTA (v_his_patient_type_alter) chinh cua ho so nhu loi HIS: uu tien doi tuong BHYT
 * (patient_type_code == key HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT), sau do LOG_TIME, ID moi nhat
 * (OrderByDescending(code == key).ThenByDescending(LOG_TIME).ThenByDescending(ID), [AX]:126).
 * Dung chung cho XML1, XML7, XML8; danh sach rong => null (DLL goi .First() chi sau khi kiem Count > 0).
 */
class PtaChinh
{
    const KEY_BHYT = 'HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT';

    /** Thu tu LOG_TIME giam dan, ID giam dan. */
    public static function giamDan($a, $b)
    {
        $c = GiaTriBang::soSanh((float) $b->log_time, (float) $a->log_time);

        return $c !== 0 ? $c : GiaTriBang::soSanh((int) $b->id, (int) $a->id);
    }

    /**
     * @param array  $ptas    cac dong v_his_patient_type_alter cua ho so
     * @param string $keyBhyt gia tri config ma doi tuong BHYT ('' khi khong co)
     * @return \stdClass|null
     */
    public static function chon(array $ptas, $keyBhyt)
    {
        if (count($ptas) === 0) {
            return null;
        }
        $keyBhyt = (string) $keyBhyt;

        return GiaTriBang::sapOnDinh($ptas, function ($a, $b) use ($keyBhyt) {
            $c = GiaTriBang::soSanh((string) $b->patient_type_code === $keyBhyt ? 1 : 0, (string) $a->patient_type_code === $keyBhyt ? 1 : 0);

            return $c !== 0 ? $c : PtaChinh::giamDan($a, $b);
        })[0];
    }
}
