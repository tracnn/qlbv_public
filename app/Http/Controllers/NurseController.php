<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DataTables;
use DB;
use Carbon\Carbon;

class NurseController extends Controller
{
    public function executeMedicationOrderIndex()
    {
        return view('nurse.execute-medication-order-index');
    }

    public function fetchDataNurseExecute(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $date_type = $request->input('date_type');
        $department_catalog = $request->input('department_catalog');

        // Check and convert date format
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
        $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

        // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'ngay_vao';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField = 'ngay_ra';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField = 'ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField = 'created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            case 'date_update':
                $dateField = 'updated_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            default:
                $dateField = 'ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
        }

        $sql = "
            SELECT
                his_department.department_name, 
                his_bed_room.bed_room_name,
                his_treatment.treatment_code, 
                his_treatment.tdl_patient_code, 
                his_treatment.tdl_patient_name, 
                his_treatment.tdl_patient_dob, 
                his_treatment.tdl_patient_gender_name,
                his_treatment.tdl_patient_phone,
                his_treatment.tdl_hein_card_number,
                his_treatment.in_time,
                his_treatment_bed_room.add_time, 
                his_bed.bed_name,
                remove_time
            FROM
                his_treatment_bed_room
            JOIN
                his_bed_room ON his_bed_room.id = his_treatment_bed_room.bed_room_id
            JOIN
                his_room ON his_room.id = his_bed_room.room_id
            JOIN
                his_department ON his_department.id = his_room.department_id
            JOIN
                his_treatment ON his_treatment.id = his_treatment_bed_room.treatment_id
            LEFT JOIN
                his_co_treatment ON his_co_treatment.id = his_treatment_bed_room.co_treatment_id
            LEFT JOIN
                his_bed ON his_bed.id = his_treatment_bed_room.bed_id
            WHERE
                his_treatment_bed_room.remove_time IS NULL
        ";

        // Add department_catalog condition if it's provided
        if (!empty($department_catalog)) {
            // Directly embedding the variable into the query string (make sure this is safe)
            $sql .= " AND his_room.department_id = '".addslashes($department_catalog)."'";
        }

        // Execute the query and get the results
        $results = DB::connection('HISPro')->select(DB::raw($sql));

        return DataTables::of($results)
        ->editColumn('tdl_patient_dob', function($result) {
            return dob($result->tdl_patient_dob);
        })
        ->editColumn('in_time', function($result) {
            return strtodatetime($result->in_time);
        })
        ->editColumn('add_time', function($result) {
            return strtodatetime($result->add_time);
        })
        ->make(true);
    }
}
