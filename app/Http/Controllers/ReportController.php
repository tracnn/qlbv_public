<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\ReportDataService;
use App\Exports\NDPDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DrugUseExport;
use App\Exports\APDataExport;
use App\Exports\DebtDataExport;
use App\Exports\AccountantRevenueDataExport;
use App\Exports\AccountantRevenueDataExportDetail;

use DB;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportDataService;

    public function __construct(ReportDataService $reportDataService)
    {
        $this->reportDataService = $reportDataService;
    }
    
    public function indexPatientCountByDepartment()
    {
        return view('administrator.index-patient-count-by-department');
    }

    public function fetchPatientCountByDepartment()
    {
        $results = $this->reportDataService->getPatientCountByDepartment();
        return response()->json($results);
    }

    public function indexDrug()
    {
        return view('drug.index');
    }

    public function fetchDrugUse(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        // Kiểm tra và chuyển đổi định dạng ngày tháng
        if (strlen($dateFrom) == 10) { // Định dạng YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Định dạng YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }
        // Chuyển đổi định dạng ngày tháng từ 'YYYY-MM-DD HH:mm:ss' sang 'YYYYMMDDHHiiss'
        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        $query = DB::connection('HISPro')
            ->table('his_sere_serv as ss')
            ->join('his_service_req as sr', 'sr.id', '=', 'ss.service_req_id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'sr.tdl_patient_type_id')
            ->join('his_service_type as st', 'st.id', '=', 'ss.tdl_service_type_id')
            ->join('his_treatment as tm', 'tm.id', '=', 'sr.treatment_id')
            ->join('his_treatment_type as tt', 'tt.id', '=', 'sr.treatment_type_id')
            ->leftJoin('his_department as re_dept', 're_dept.id', '=', 'ss.tdl_request_department_id')
            ->leftJoin('his_medicine as mc', 'mc.id', '=', 'ss.medicine_id')
            ->join('his_medicine_type as mt', 'mt.id', '=', 'mc.medicine_type_id')
            ->join('his_medicine_use_form as muf', 'muf.id', '=', 'mt.medicine_use_form_id')
            ->select(
                'sr.tdl_patient_name',
                'sr.tdl_patient_gender_name',
                'sr.tdl_patient_dob',
                'sr.tdl_treatment_code',
                'sr.tdl_patient_code',
                'sr.service_req_code',
                'sr.request_username',
                'tm.tdl_hein_card_number',
                'pt.patient_type_name',
                'sr.icd_code',
                'sr.icd_name',
                'sr.icd_sub_code',
                'sr.icd_text',
                'tm.in_time',
                'tm.out_time',
                'tt.treatment_type_name',
                'st.service_type_name',
                'ss.amount',
                'ss.original_price',
                're_dept.department_name',
                'ss.tdl_intruction_time',
                'ss.tdl_hein_service_bhyt_name',
                'mt.medicine_type_code',
                'mt.medicine_type_name',
                'mt.concentra',
                'mt.active_ingr_bhyt_code',
                'mt.active_ingr_bhyt_name',
                'muf.medicine_use_form_name'
            )
            ->where('ss.is_delete', 0)
            ->whereNull('ss.is_expend')
            ->where('ss.tdl_service_type_id', 6)
            ->whereBetween('sr.intruction_time', [$formattedDateFrom, $formattedDateTo]);

        return DataTables::of($query)
        ->make(true);
    }

    public function indexCVCRReport()
    {
        return view('administrator.cvcrreport');
    }

    public function fetchCVCRData(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Kiểm tra và chuyển đổi định dạng ngày tháng
        if (strlen($dateFrom) == 10) { // Định dạng YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Định dạng YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        // Chuyển đổi định dạng ngày tháng từ 'YYYY-MM-DD HH:mm:ss' sang 'YYYYMMDDHHiiss'
        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

       // Truy vấn dữ liệu
        $sql = "
            WITH clinic_visits AS (
                SELECT 
                    his_service_req.tdl_treatment_code, 
                    ex.execute_room_code,
                    ex.execute_room_name,
                    COUNT(DISTINCT his_service_req.tdl_patient_id) AS total_patients
                FROM 
                    his_service_req
                LEFT JOIN 
                    his_execute_room ex ON ex.room_id = his_service_req.execute_room_id
                JOIN
                    his_treatment ON his_treatment.id = his_service_req.treatment_id
                JOIN
                    his_sere_serv ON his_sere_serv.service_req_id = his_service_req.id
                WHERE 
                    his_service_req.intruction_time BETWEEN :formattedDateFrom AND :formattedDateTo
                    AND ex.is_exam = 1
                    AND his_service_req.is_delete = 0
                    AND his_service_req.treatment_type_id = 1
                    AND his_service_req.service_req_type_id = 1
                    AND his_sere_serv.is_delete = 0
                    AND his_sere_serv.is_expend IS NULL
                    AND his_sere_serv.is_no_execute IS NULL
                GROUP BY 
                    his_service_req.tdl_treatment_code, ex.execute_room_code, ex.execute_room_name
            ),
            
            exam_costs AS (
                SELECT 
                    his_sere_serv.tdl_treatment_code, 
                    ex.execute_room_code,
                    ex.execute_room_name,
                    SUM(amount * vir_price) AS exam_cost
                FROM 
                    his_sere_serv 
                JOIN
                    his_execute_room ex ON ex.room_id = his_sere_serv.tdl_execute_room_id
                WHERE
                    his_sere_serv.tdl_treatment_code IN (
                        SELECT 
                            DISTINCT his_sere_serv.tdl_treatment_code
                        FROM 
                            his_sere_serv
                        LEFT JOIN 
                            his_execute_room ex ON ex.room_id = his_sere_serv.tdl_execute_room_id
                        JOIN 
                            his_service_req ON his_service_req.id = his_sere_serv.service_req_id
                        JOIN
                            his_treatment ON his_treatment.id = his_sere_serv.tdl_treatment_id
                        WHERE 
                            his_service_req.intruction_time BETWEEN :formattedDateFrom AND :formattedDateTo
                            AND his_sere_serv.is_delete = 0
                            AND his_sere_serv.is_no_execute IS NULL
                            AND his_sere_serv.is_expend IS NULL
                            AND ex.is_exam = 1
                            AND his_service_req.is_delete = 0
                            AND his_treatment.tdl_treatment_type_id = 1
                    )
                    AND his_sere_serv.tdl_service_type_id = 1
                    AND ex.is_exam = 1
                GROUP BY
                    his_sere_serv.tdl_treatment_code, ex.execute_room_code, ex.execute_room_name
            ),
            
            drug_and_other_costs AS (
                SELECT 
                    his_sere_serv.tdl_treatment_code, 
                    rq.execute_room_code,
                    rq.execute_room_name,
                    SUM(CASE WHEN his_sere_serv.tdl_service_type_id = 6 THEN amount * vir_price ELSE 0 END) AS drug_cost,
                    COUNT(DISTINCT CASE WHEN his_sere_serv.tdl_service_type_id = 6 THEN his_sere_serv.tdl_patient_id ELSE NULL END) AS patients_with_drug,
                    SUM(CASE WHEN his_sere_serv.tdl_service_type_id NOT IN (1, 6) THEN amount * vir_price ELSE 0 END) AS other_cost
                FROM 
                    his_sere_serv 
                JOIN
                    his_execute_room rq ON rq.room_id = his_sere_serv.tdl_request_room_id
                WHERE
                    his_sere_serv.tdl_treatment_code IN (
                        SELECT 
                            DISTINCT his_sere_serv.tdl_treatment_code
                        FROM 
                            his_sere_serv
                        LEFT JOIN 
                            his_execute_room ex ON ex.room_id = his_sere_serv.tdl_execute_room_id
                        JOIN 
                            his_service_req ON his_service_req.id = his_sere_serv.service_req_id
                        JOIN
                            his_treatment ON his_treatment.id = his_sere_serv.tdl_treatment_id
                        WHERE 
                            his_service_req.intruction_time BETWEEN :formattedDateFrom AND :formattedDateTo
                            AND his_sere_serv.is_delete = 0
                            AND his_sere_serv.is_no_execute IS NULL
                            AND his_sere_serv.is_expend IS NULL
                            AND ex.is_exam = 1
                            AND his_service_req.is_delete = 0
                            AND his_treatment.tdl_treatment_type_id = 1
                    )
                    AND rq.is_exam = 1
                GROUP BY
                    his_sere_serv.tdl_treatment_code, rq.execute_room_code, rq.execute_room_name
            )
            
            SELECT 
                clinic_visits.execute_room_code,
                clinic_visits.execute_room_name,
                SUM(clinic_visits.total_patients) AS total_patients,
                SUM(COALESCE(exam_costs.exam_cost, 0) + COALESCE(drug_and_other_costs.drug_cost, 0) + COALESCE(drug_and_other_costs.other_cost, 0)) AS total_cost,
                SUM(COALESCE(drug_and_other_costs.patients_with_drug, 0)) AS total_patients_with_drug,
                SUM(COALESCE(drug_and_other_costs.drug_cost, 0)) AS total_drug_cost
            FROM 
                clinic_visits
            LEFT JOIN
                exam_costs ON clinic_visits.tdl_treatment_code = exam_costs.tdl_treatment_code AND clinic_visits.execute_room_code = exam_costs.execute_room_code
            LEFT JOIN
                drug_and_other_costs ON clinic_visits.tdl_treatment_code = drug_and_other_costs.tdl_treatment_code AND clinic_visits.execute_room_code = drug_and_other_costs.execute_room_code
            GROUP BY
                clinic_visits.execute_room_code, clinic_visits.execute_room_name";

        // Thực hiện truy vấn và lấy kết quả
        $results = DB::connection('HISPro')->select(DB::raw($sql), [
            'formattedDateFrom' => $formattedDateFrom,
            'formattedDateTo' => $formattedDateTo
        ]);

        // Trả về kết quả dưới dạng DataTables và sử dụng editColumn và addColumn
        return DataTables::of($results)
        ->editColumn('total_cost', function($result) {
            return number_format($result->total_cost);
        })
        ->editColumn('total_drug_cost', function($result) {
            return number_format($result->total_drug_cost);
        })
        ->addColumn('avg_total_cost', function($result) {
            return $result->total_patients > 0 ? number_format($result->total_cost / $result->total_patients) : 0;
        })
        ->addColumn('avg_drug_cost', function($result) {
            return $result->total_patients_with_drug > 0 ? number_format($result->total_drug_cost / $result->total_patients_with_drug) : 0;
        })
        ->addColumn('drug_percentage', function($result) {
            return $result->total_patients > 0 ? number_format(($result->total_patients_with_drug / $result->total_patients) * 100, 2) . '%' : '0.00%';
        })
        ->addColumn('drug_cost_percentage', function($result) {
            return $result->total_cost > 0 ? number_format(($result->total_drug_cost / $result->total_cost) * 100, 2) . '%' : '0.00%';
        })
        ->make(true);
    }

    public function exportDrugUse(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Kiểm tra và chuyển đổi định dạng ngày tháng
        if (strlen($dateFrom) == 10) { // Định dạng YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Định dạng YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }
        // Chuyển đổi định dạng ngày tháng từ 'YYYY-MM-DD HH:mm:ss' sang 'YYYYMMDDHHiiss'
        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        $fileName = 'druguse_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new DrugUseExport($formattedDateFrom, $formattedDateTo), $fileName);
    }

    public function indexNDPReport()
    {
        return view('administrator.ndpreport');
    }

    public function fetchNDPData(Request $request)
    {
        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsNdp($request);

        // Execute the query and get the results
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        // Return the results as DataTables and use editColumn and addColumn
        return DataTables::of($results)
        ->editColumn('drug_count', function($result) {
            return number_format($result->drug_count);
        })
        ->editColumn('mety_count', function($result) {
            return number_format($result->mety_count);
        })
        ->editColumn('prescription_type_id', function($result) {
            switch ($result->prescription_type_id) {
                case 1:
                    return "Đơn tân dược";
                case 2:
                    return "Đơn YHCT";
                case 3:
                    return "Đơn CLS";
                default:
                    return $result->prescription_type_id;
            }
        })
        ->make(true);
    }

    public function exportNDPData(Request $request)
    {
        $drug_req_type = $request->input('drug_req_type');
        $prescription_type = $request->input('prescription_type');
        $treatment_code = $request->input('treatment_code');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $fileName = 'ndp_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new NDPDataExport($drug_req_type, $prescription_type, $treatment_code, $date_from, $date_to),
            $fileName);
    }

    public function paymentAccountant()
    {
        return view('administrator.payment-acountant-report');
    }

    public function fetchPaymentAccountant(Request $request)
    {
        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsPa($request);

        // Execute the query and get the results
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        // Return the results as DataTables and use editColumn and addColumn
        return DataTables::of($results)
        ->editColumn('amount', function($result) {
            return number_format($result->amount);
        })
        ->editColumn('tdl_patient_dob', function($result) {
            return strtodate($result->tdl_patient_dob);
        })
        ->editColumn('transaction_time', function($result) {
            return strtodatetime($result->transaction_time);
        })
        ->make(true);
    }

    public function exportAPData(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $fileName = 'apd_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new APDataExport($date_from, $date_to), $fileName);
    }

    public function listPatientPt(Request $request)
    {
        $date_from = $request->input('date_from') ?? null;
        $date_to = $request->input('date_to') ?? null;
        $date_type = $request->input('date_type') ?? null;
        return view('administrator.list-patient-pt', compact('date_from', 'date_to', 'date_type'));
    }

    public function fetchPatientPt(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $date_type = $request->input('date_type');
        // Kiểm tra và chuyển đổi định dạng ngày tháng
        if (strlen($dateFrom) == 10) { // Định dạng YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Định dạng YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }
        // Chuyển đổi định dạng ngày tháng từ 'YYYY-MM-DD HH:mm:ss' sang 'YYYYMMDDHHiiss'
        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');
        
        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'tm.in_time';
                break;
            case 'date_out':
                $dateField = 'tm.out_time';
                break;
            case 'date_payment':
                $dateField = 'tm.fee_lock_time';
                break;
            case 'date_intruction':
                $dateField = 'sr.intruction_time';
                break;
            default:
                $dateField = 'sr.intruction_time';
                break;
        }

        $service_req_type_pt = [10];

        $query = DB::connection('HISPro')
            ->table('his_sere_serv as ss')
            ->join('his_service_req as sr', 'sr.id', '=', 'ss.service_req_id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'sr.tdl_patient_type_id')
            ->join('his_treatment as tm', 'tm.id', '=', 'sr.treatment_id')
            ->join('his_patient as pa', 'pa.id', '=', 'tm.patient_id')
            ->join('his_treatment_type as tt', 'tt.id', '=', 'sr.treatment_type_id')
            ->leftJoin('his_department as re_dept', 're_dept.id', '=', 'ss.tdl_request_department_id')
            ->select(
                'sr.tdl_patient_name',
                'sr.tdl_patient_gender_name',
                'sr.tdl_patient_dob',
                'sr.tdl_treatment_code',
                'sr.tdl_patient_code',
                'sr.request_username',
                'sr.execute_username',
                'pt.patient_type_name',
                'tm.in_time',
                'tm.out_time',
                'tm.tdl_patient_phone',
                'tm.tdl_patient_address',
                'tm.tdl_patient_district_name',
                'pa.relative_name',
                'pa.relative_mobile',
                'tt.treatment_type_name',
                'ss.tdl_service_name',
                're_dept.department_name',
                'sr.intruction_time',
            )
            ->where('ss.is_delete', 0)
            ->whereIn('sr.service_req_type_id', $service_req_type_pt)
            ->whereNull('ss.is_expend')
            ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

        return DataTables::of($query)
        ->editColumn('tdl_patient_dob', function($result) {
            return dob($result->tdl_patient_dob);
        })
        ->editColumn('in_time', function($result) {
            return strtodatetime($result->in_time);
        })
        ->editColumn('out_time', function($result) {
            return strtodatetime($result->out_time);
        })
        ->editColumn('intruction_time', function($result) {
            return strtodatetime($result->intruction_time);
        })
        ->addColumn('action', function($result) {
            return '<a href="' .route('treatment-result.search',['treatment_code'=>$result->tdl_treatment_code]) .'" 
                class="btn btn-sm btn-primary" target="_blank">
                <span class="glyphicon glyphicon-eye-open"></span> Chi tiết EMR</a>'
            ;
        })
        ->toJson();
    }

    public function debtAccountant()
    {
        return view('administrator.list-debt-accountant');
    }

    public function fetchAccountantDebt(Request $request)
    {
        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsDebt($request);

        // Execute the query and get the results
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        // Return the results as DataTables and use editColumn and addColumn
        return DataTables::of($results)
        ->editColumn('tdl_patient_dob', function($result) {
            return strtodate($result->tdl_patient_dob);
        })
        ->editColumn('in_time', function($result) {
            return strtodatetime($result->in_time);
        })
        ->editColumn('out_time', function($result) {
            return strtodatetime($result->out_time);
        })
        ->editColumn('tam_ung', function($result) {
            return number_format($result->tam_ung);
        })
        ->editColumn('hoan_ung', function($result) {
            return number_format($result->hoan_ung);
        })
        ->editColumn('da_thanh_toan', function($result) {
            return number_format($result->da_thanh_toan);
        })
        ->editColumn('tu_nhap', function($result) {
            return number_format($result->tu_nhap);
        })
        ->editColumn('xuat_ban', function($result) {
            return number_format($result->xuat_ban);
        })
        ->editColumn('total_price', function($result) {
            return number_format($result->total_price);
        })
        ->editColumn('total_hein_price', function($result) {
            return number_format($result->total_hein_price);
        })
        ->editColumn('total_patient_price', function($result) {
            return number_format($result->total_patient_price);
        })
        ->editColumn('can_thanh_toan', function($result) {
            return number_format($result->can_thanh_toan);
        })
        ->toJson();
    }

    public function exportDebtData(Request $request)
    {
        $fileName = 'debt_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new DebtDataExport($request), $fileName);
    }

    public function accoutantRevenue()
    {
        return view('administrator.list-accountant-revenue');
    }
    public function fetchAccountantRevenue(Request $request)
    {
        list($sql, $bindings) = $this->reportDataService->buildSqlQueryAndBindingsAccountantRevenue($request);

        // Execute the query and get the results
        $results = DB::connection('HISPro')->select(DB::raw($sql), $bindings);

        // Return the results as DataTables and use editColumn and addColumn
        return DataTables::of($results)
        ->editColumn('xn', function($result) {
            return number_format($result->xn);
        })
        ->editColumn('ha', function($result) {
            return number_format($result->ha);
        })
        ->editColumn('th', function($result) {
            return number_format($result->th);
        })
        ->editColumn('ma', function($result) {
            return number_format($result->ma);
        })
        ->editColumn('tt', function($result) {
            return number_format($result->tt);
        })
        ->editColumn('vt', function($result) {
            return number_format($result->vt);
        })
        ->editColumn('ns', function($result) {
            return number_format($result->ns);
        })
        ->editColumn('cn', function($result) {
            return number_format($result->cn);
        })
        ->editColumn('sa', function($result) {
            return number_format($result->sa);
        })
        ->editColumn('pt', function($result) {
            return number_format($result->pt);
        })
        ->editColumn('gb', function($result) {
            return number_format($result->gb);
        })
        ->editColumn('an', function($result) {
            return number_format($result->an);
        })
        ->editColumn('cl', function($result) {
            return number_format($result->cl);
        })
        ->editColumn('kh', function($result) {
            return number_format($result->kh);
        })
        ->editColumn('gi', function($result) {
            return number_format($result->gi);
        })
        ->toJson();
    }

    public function exportAccountantRevenue(Request $request)
    {
        $fileName = 'accountant_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new AccountantRevenueDataExport($request), $fileName);
    }

    public function exportAccountantRevenueDetail(Request $request)
    {
        $fileName = 'accountant_data_detail_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new AccountantRevenueDataExportDetail($request), $fileName);
    }

}
