<?php

namespace App\Services\OrderCheck;

use Illuminate\Support\Facades\DB;

/**
 * Doc thong tin ho so cua mot dot dieu tri tu HIS (Oracle).
 *
 * Tach rieng khoi TreatmentIssueService co chu dich: hai nguon nam tren hai CSDL khac
 * nhau, Oracle hong khong duoc keo theo phan loi doc tu MySQL.
 */
class TreatmentProfileService
{
    /**
     * @param  string|null $treatmentCode
     * @return array|null  null khi ma rong hoac khong tim thay ho so
     */
    public function cua($treatmentCode)
    {
        $ma = trim((string) $treatmentCode);

        if ($ma === '') {
            return null;
        }

        $d = DB::connection('HISPro')->table('his_treatment')
            // TAT CA deu leftJoin: ho so khuyet du lieu chinh la ho so can soi. Inner
            // join lam no bien mat va nguoi dung tuong go sai ma.
            ->leftJoin('his_gender', 'his_gender.id', '=', 'his_treatment.tdl_patient_gender_id')
            ->leftJoin('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->leftJoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->leftJoin('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
            ->where('his_treatment.treatment_code', $ma)
            ->select([
                'his_treatment.treatment_code',
                'his_treatment.tdl_patient_name',
                'his_treatment.tdl_patient_dob',
                'his_treatment.tdl_hein_card_number',
                'his_treatment.tdl_hein_medi_org_code',
                'his_treatment.tdl_hein_card_from_time',
                'his_treatment.tdl_hein_card_to_time',
                'his_treatment.in_time',
                'his_treatment.out_time',
                'his_gender.gender_code',
                'his_gender.gender_name',
                'his_department.department_name',
                'his_treatment_type.treatment_type_name',
                // Ma CO SO DIEU TRI. KHONG duoc thay bang tdl_hein_medi_org_code - cot do
                // la noi DKBD ghi tren the cua benh nhan. Do tren 45.995 ho so: hai gia
                // tri chi trung nhau 0,5%.
                'his_branch.hein_medi_org_code as ma_cskcb',
            ])
            ->first();

        if (!$d) {
            return null;
        }

        return [
            'treatment_code'           => $d->treatment_code,
            'patient_name'             => $d->tdl_patient_name,
            'patient_dob'              => $d->tdl_patient_dob,
            'patient_dob_text'         => $this->ngay($d->tdl_patient_dob, true),
            'gender_code'              => $d->gender_code,
            'gender_name'              => $d->gender_name,
            'hein_card_number'         => $d->tdl_hein_card_number,
            'hein_medi_org_code'       => $d->tdl_hein_medi_org_code,
            'hein_card_from_time_text' => $this->ngay($d->tdl_hein_card_from_time),
            'hein_card_to_time_text'   => $this->ngay($d->tdl_hein_card_to_time),
            'department_name'          => $d->department_name,
            'treatment_type_name'      => $d->treatment_type_name,
            'in_time_text'             => $this->ngay($d->in_time),
            'out_time_text'            => $this->ngay($d->out_time),
            'ma_cskcb'                 => $d->ma_cskcb,
        ];
    }

    /**
     * Chuoi ngay cua HIS (Ymd / YmdHi / YmdHis) sang dang nguoi doc.
     * Dung lai hai helper san co thay vi tu viet: chung da xu ly ca truong hop ngay sinh
     * chi co nam (0000 o giua).
     */
    protected function ngay($gia, $laNgaySinh = false)
    {
        $gia = trim((string) $gia);

        if ($gia === '') {
            return null;
        }

        return $laNgaySinh ? dob($gia) : strtodatetime($gia);
    }
}
