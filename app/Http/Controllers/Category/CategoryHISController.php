<?php

namespace App\Http\Controllers\Category;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\RoleUser;
use App\CustomUser;
use App\Role;

class CategoryHISController extends Controller
{
    public function listKskContract()
    {
        return DB::connection('HISPro')
        ->table('his_ksk_contract')
        ->join('his_work_place', 'his_work_place.id', '=', 'his_ksk_contract.work_place_id')
        ->where('his_ksk_contract.is_active', 1)
        ->where('his_ksk_contract.is_delete', 0)
        ->select('his_ksk_contract.id', 'his_ksk_contract.ksk_contract_code', 'his_work_place.id as work_place_id', 'his_work_place.work_place_code', 'his_work_place.work_place_name')
        ->get();
    }

    public function listDepartmentCatalog()
    {
        return DB::connection('HISPro')
        ->table('his_department')
        ->where('his_department.is_active', 1)
        ->where('his_department.is_delete', 0)
        ->select('his_department.id', 'his_department.department_code', 'his_department.department_name')
        ->get();
    }

    public function listPatientType()
    {
        return DB::connection('HISPro')
        ->table('his_patient_type')
        ->where('his_patient_type.is_active', 1)
        ->where('his_patient_type.is_delete', 0)
        ->select('his_patient_type.id', 'his_patient_type.patient_type_code', 'his_patient_type.patient_type_name')
        ->get();
    }

    public function listTreatmentType()
    {
        return DB::connection('HISPro')
        ->table('his_treatment_type')
        ->where('his_treatment_type.is_active', 1)
        ->where('his_treatment_type.is_delete', 0)
        ->select('his_treatment_type.id', 'his_treatment_type.treatment_type_code', 'his_treatment_type.treatment_type_name')
        ->get();
    }

    public function listTreatmentEndType()
    {
        return DB::connection('HISPro')
        ->table('his_treatment_end_type')
        ->where('his_treatment_end_type.is_active', 1)
        ->where('his_treatment_end_type.is_delete', 0)
        ->select('his_treatment_end_type.id', 'his_treatment_end_type.treatment_end_type_code', 'his_treatment_end_type.treatment_end_type_name')
        ->get();
    }

    public function fetchImportedBy()
    {
        $userIds = RoleUser::whereHas('role', function ($query) {
            $query->whereIn('name', ['superadministrator', 'administrator', 'xml-man']);
        })->pluck('user_id')->toArray();

        return CustomUser::whereIn('id', $userIds)
        ->where('is_active', 1)
        ->where('is_delete', 0)
        ->select('id', 'loginname', 'username')
        ->get();
    }

    public function listDocumentType()
    {
        return DB::connection('EMR_RS')
        ->table('emr_document_type')
        ->where('emr_document_type.is_active', 1)
        ->where('emr_document_type.is_delete', 0)
        ->select('emr_document_type.id', 'emr_document_type.document_type_code', 'emr_document_type.document_type_name')
        ->get();
    }
}