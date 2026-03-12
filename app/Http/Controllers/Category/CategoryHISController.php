<?php

namespace App\Http\Controllers\Category;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use DataTables;

use App\RoleUser;
use App\CustomUser;
use App\Role;
use App\Services\HisServicePriceSearchService;

class CategoryHISController extends Controller
{
    /**
     * @var HisServicePriceSearchService
     */
    protected $servicePriceService;

    public function __construct(HisServicePriceSearchService $servicePriceService)
    {
        $this->servicePriceService = $servicePriceService;
    }

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

    /**
     * View tra cứu giá dịch vụ từ HIS (HISPro) dùng Datatables.
     */
    public function indexServicePrice()
    {
        $patientTypes = $this->servicePriceService->getPatientTypes();

        return view('category.his.service_price', compact('patientTypes'));
    }

    /**
     * Dữ liệu Datatables server-side cho tra cứu giá dịch vụ.
     */
    public function fetchServicePrice(Request $request)
    {
        $patientTypes = $this->servicePriceService->getPatientTypes();
        $query = $this->servicePriceService->buildServicePriceQuery($patientTypes);

        $dt = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if ($search) {
                    $search = mb_strtoupper($search, 'UTF-8');

                    $query->where(function ($q) use ($search) {
                        $q->whereRaw('UPPER("SERVICE_CODE") LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('UPPER("SERVICE_NAME") LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('UPPER("SERVICE_TYPE_CODE") LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('UPPER("SERVICE_TYPE_NAME") LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('UPPER("SERVICE_UNIT_CODE") LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('UPPER("SERVICE_UNIT_NAME") LIKE ?', ["%{$search}%"]);
                    });
                }
            }, true);

        foreach ($patientTypes as $pt) {
            $colName = 'price_' . $pt->id;
            $dt->editColumn($colName, function ($row) use ($colName) {
                $val = $row->$colName ?? $row->{strtoupper($colName)} ?? null;
                return $val ? number_format($val, 0, ',', '.') : '';
            });
        }

        return $dt
        ->editColumn('from_time', function ($row) {
            return $row->from_time ? strtodate($row->from_time) : '';
        })
        ->make(true);
    }
}
