<?php

namespace App\Http\Controllers\KHTH;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Category\CategoryHISController;

use DataTables;
use DB;
use App\Exports\KSKExport;
use Carbon\Carbon;

class KskController extends Controller
{
    private function health_rank()
    {
        return DB::connection('HISPro')
        ->table('his_health_exam_rank')
        ->where('is_active', 1)
        ->where('is_delete', 0)
        ->get();
    }

    private function work_place()
    {
        return DB::connection('HISPro')
        ->table('his_work_place')
        ->select('his_work_place.id', 'his_work_place.work_place_code', 'his_work_place.work_place_name')
        ->where('his_work_place.is_active', 1)
        ->where('his_work_place.is_delete', 0)
        ->get();
    }

    public function index(Request $request)
    {

        $health_rank = $this->health_rank();
        $work_place = $this->work_place();
        return view('khth.ksk.index',
            compact('health_rank','work_place'));
    }

    public function get_danhsach(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $kskContract = $request->input('ksk_contract');
        $serviceReqStt = $request->input('service_req_stt');
        
        if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHis');

        $execute_room_id = [58,126,642];
     
        $model = DB::connection('HISPro')
        ->table('his_service_req')
        ->leftJoin('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
        ->leftJoin('his_dhst', 'his_dhst.id', '=', 'his_service_req.dhst_id')
        ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
        ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
        ->select('his_service_req.treatment_id','his_service_req.id', 'his_service_req_stt.service_req_stt_name', 'his_service_req.service_req_code', 
        'his_service_req.num_order', 'his_service_req.service_req_stt_id', 'his_service_req.dhst_id',
        'his_service_req.tdl_treatment_code', 'his_service_req.tdl_patient_code', 'his_service_req.tdl_patient_name', 
        'his_service_req.tdl_patient_dob', 'his_service_req.tdl_patient_gender_name',
        'his_execute_room.execute_room_name',
        'his_dhst.weight','his_dhst.height','his_dhst.blood_pressure_max','his_dhst.blood_pressure_min','his_dhst.pulse','his_dhst.note',
        'his_service_req.part_exam','his_service_req.part_exam_circulation','his_service_req.part_exam_respiratory',
        'part_exam_digestion','part_exam_kidney_urology','part_exam_neurological','part_exam_muscle_bone',
        'part_exam_oend','part_exam_mental','part_exam_nutrition','part_exam_motion','part_exam_dermatology',
        'part_exam_stomatology','part_exam_lower_jaw','part_exam_upper_jaw',
        'part_exam_ear','part_exam_nose','part_exam_throat','part_exam_ear_right_normal',
        'part_exam_ear_right_whisper','part_exam_ear_left_normal','part_exam_ear_left_whisper',
        'part_exam_horizontal_sight','part_exam_vertical_sight','part_exam_eye_blind_color','part_exam_eye',
        'part_exam_eye_tension_left','part_exam_eye_tension_right','part_exam_eyesight_left',
        'part_exam_eyesight_right','part_exam_eyesight_glass_left','part_exam_eyesight_glass_right','part_exam_obstetric',
        'his_service_req.health_exam_rank_id','his_service_req.note as service_req_note', 'his_service_req.treatment_instruction',
        'his_service_req.pathological_history','his_treatment.tdl_patient_avatar_url','his_service_req.subclinical',
        'his_treatment.tdl_patient_phone','his_service_req.next_treatment_instruction')
        ->whereBetween('his_service_req.intruction_time', [$formattedDateFrom, $formattedDateTo])
        ->where([
            ['his_service_req.is_active', 1],
            ['his_service_req.is_delete', 0],
            ['his_service_req.service_req_type_id', 1],
        ])
        ->whereIn('his_service_req.execute_room_id', $execute_room_id);

        if ($kskContract) {
            $model = $model->where('his_treatment.tdl_ksk_contract_id', $kskContract);
        }
        if ($serviceReqStt) {
            $model = $model->where('his_service_req.service_req_stt_id', $serviceReqStt);
        }

        return DataTables::of($model)
        ->editColumn('tdl_patient_dob', function($result) {
            return dob($result->tdl_patient_dob);
        })
        ->editColumn('tdl_patient_phone', function($result) {
            return '<a href="tel:' .$result->tdl_patient_phone .'">' .$result->tdl_patient_phone .'</a>';
        })
        ->editColumn('service_req_stt_name', function($result) {
            switch ($result->service_req_stt_id) {
                case '1':
                    $rtnData = '<span class="label label-danger">' .$result->service_req_stt_name .'</span>';
                    break;
                case '3':
                    $rtnData = '<span class="label label-success">' .$result->service_req_stt_name .'</span>';
                    break;                
                default:
                    $rtnData = $result->service_req_stt_name;
                    break;
            }
            return $rtnData;
        })
        ->addColumn('action', function ($result) {
            switch ($result->service_req_stt_id) {
                case '1':
                    $rtnData  = '';
                    if (\Auth::user()->hasPermission('ksk-tiepdon') || \Auth::user()->hasRole('superadministrator')) {
                        $rtnData = '<a class="btn btn-sm btn-info edit-modal-tiepdon" href="#" data-title="' .$result->tdl_patient_name
                                .' - ' .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name
                                .'" data-tdl_patient_avatar_url_tiepdon="' .$result->tdl_patient_avatar_url
                                .'" data-id="' .$result->id .'"><span class="glyphicon glyphicon-plus"></span> Tiếp đón</a>';
                    }
                    break;
                case '3':
                    $rtnData = '';
                    break;                
                default:
                $rtnData = '';

                // Nút "Thể lực"
                $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-theluc" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                    .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                    .'" data-weight="' .$result->weight .'" data-height="' .$result->height 
                    .'" data-blood_pressure_max="' .$result->blood_pressure_max 
                    .'" data-blood_pressure_min="' .$result->blood_pressure_min 
                    .'" data-pulse="' .$result->pulse .'" data-note="' .$result->note 
                    .'" data-id="' .$result->id .'" data-dhst_id="' .$result->dhst_id .'">
                    <span class="glyphicon glyphicon-check"></span> Thể lực</a> ';

                // Nút "Nội, Ngoại, Da liễu"
                $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-noi" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                    .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                    .'" data-theluc="Mạch: ' .$result->pulse .'; HA: ' .$result->blood_pressure_max .'/' .$result->blood_pressure_min 
                    .'" data-pathological_history="' .$result->pathological_history 
                    .'" data-part_exam="' .$result->part_exam 
                    .'" data-part_exam_circulation="' .$result->part_exam_circulation 
                    .'" data-part_exam_respiratory="' .$result->part_exam_respiratory 
                    .'" data-part_exam_digestion="' .$result->part_exam_digestion 
                    .'" data-part_exam_kidney_urology="' .$result->part_exam_kidney_urology 
                    .'" data-part_exam_neurological="' .$result->part_exam_neurological 
                    .'" data-part_exam_muscle_bone="' .$result->part_exam_muscle_bone 
                    .'" data-part_exam_oend="' .$result->part_exam_oend 
                    .'" data-part_exam_mental="' .$result->part_exam_mental 
                    .'" data-part_exam_nutrition="' .$result->part_exam_nutrition 
                    .'" data-part_exam_motion="' .$result->part_exam_motion 
                    .'" data-part_exam_dermatology="' .$result->part_exam_dermatology 
                    .'" data-id="' .$result->id .'">
                    <span class="glyphicon glyphicon-check"></span> Nội, Ngoại, Da liễu</a> ';

                // Nút "RHM" (Nếu có quyền)
                if (\Auth::user()->hasPermission('ksk-rhm') || \Auth::user()->hasRole('superadministrator')) {
                    $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-rhm" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                        .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                        .'" data-part_exam_stomatology="' .$result->part_exam_stomatology 
                        .'" data-part_exam_lower_jaw="' .$result->part_exam_lower_jaw 
                        .'" data-part_exam_upper_jaw="' .$result->part_exam_upper_jaw 
                        .'" data-id="' .$result->id .'">
                        <span class="glyphicon glyphicon-check"></span> RHM</a> ';
                }

                // Nút "TMH" (Nếu có quyền)
                if (\Auth::user()->hasPermission('ksk-tmh') || \Auth::user()->hasRole('superadministrator')) {
                    $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-tmh" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                        .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                        .'" data-part_exam_ear="' .$result->part_exam_ear 
                        .'" data-part_exam_nose="' .$result->part_exam_nose 
                        .'" data-part_exam_throat="' .$result->part_exam_throat 
                        .'" data-part_exam_ear_right_normal="' .$result->part_exam_ear_right_normal 
                        .'" data-part_exam_ear_right_whisper="' .$result->part_exam_ear_right_whisper 
                        .'" data-part_exam_ear_left_normal="' .$result->part_exam_ear_left_normal 
                        .'" data-part_exam_ear_left_whisper="' .$result->part_exam_ear_left_whisper 
                        .'" data-id="' .$result->id .'">
                        <span class="glyphicon glyphicon-check"></span> TMH</a> ';
                }

                // Nút "Mắt" (Nếu có quyền)
                if (\Auth::user()->hasPermission('ksk-mat') || \Auth::user()->hasRole('superadministrator')) {
                    $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-mat" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                        .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                        .'" data-part_exam_horizontal_sight="' .$result->part_exam_horizontal_sight 
                        .'" data-part_exam_vertical_sight="' .$result->part_exam_vertical_sight 
                        .'" data-part_exam_eye_blind_color="' .$result->part_exam_eye_blind_color 
                        .'" data-part_exam_eye="' .$result->part_exam_eye 
                        .'" data-part_exam_eye_tension_left="' .$result->part_exam_eye_tension_left 
                        .'" data-part_exam_eye_tension_right="' .$result->part_exam_eye_tension_right 
                        .'" data-part_exam_eyesight_left="' .$result->part_exam_eyesight_left 
                        .'" data-part_exam_eyesight_right="' .$result->part_exam_eyesight_right 
                        .'" data-part_exam_eyesight_glass_left="' .$result->part_exam_eyesight_glass_left 
                        .'" data-part_exam_eyesight_glass_right="' .$result->part_exam_eyesight_glass_right 
                        .'" data-id="' .$result->id .'">
                        <span class="glyphicon glyphicon-check"></span> Mắt</a> ';
                }

                // Nút "Sản phụ khoa" (Nếu có quyền)
                if (\Auth::user()->hasPermission('ksk-san') || \Auth::user()->hasRole('superadministrator')) {
                    $rtnData .= '<a class="btn btn-sm btn-primary edit-modal-san" href="#" data-title="' .$result->tdl_patient_name .' - ' 
                        .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
                        .'" data-part_exam_obstetric="' .$result->part_exam_obstetric 
                        .'" data-id="' .$result->id .'">
                        <span class="glyphicon glyphicon-check"></span> Sản phụ khoa</a>';
                }
                    break;
            }

            $rtnData = $rtnData .'<a class="btn btn-sm btn-danger edit-modal-tuvan" href="#" data-title="' .$result->tdl_patient_name
            .' - ' .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name
            .'" data-next_treatment_instruction="' .$result->next_treatment_instruction
            .'" data-tu_van_id="' .$result->id .'"><span class="glyphicon glyphicon-headphones"></span> Tư vấn</a>';
            
            $rtnData = $rtnData .'<a class="btn btn-sm btn-warning edit-modal-tongket" href="#" data-title="' .$result->tdl_patient_name 
            .' - ' .substr($result->tdl_patient_dob, 0, 4) .' - ' .$result->tdl_patient_gender_name 
            .'" data-weight="' .$result->weight 
            .'" data-height="' .$result->height 
            .'" data-blood_pressure_max="' .$result->blood_pressure_max 
            .'" data-blood_pressure_min="' .$result->blood_pressure_min 
            .'" data-pulse="' .$result->pulse 
            .'" data-note="' .$result->note
            .'" data-part_exam="' .$result->part_exam
            .'" data-part_exam_circulation="' .$result->part_exam_circulation
            .'" data-part_exam_respiratory="' .$result->part_exam_respiratory
            .'" data-part_exam_digestion="' .$result->part_exam_digestion
            .'" data-part_exam_kidney_urology="' .$result->part_exam_kidney_urology
            .'" data-part_exam_neurological="' .$result->part_exam_neurological
            .'" data-part_exam_muscle_bone="' .$result->part_exam_muscle_bone
            .'" data-part_exam_oend="' .$result->part_exam_oend
            .'" data-part_exam_mental="' .$result->part_exam_mental
            .'" data-part_exam_nutrition="' .$result->part_exam_nutrition
            .'" data-part_exam_motion="' .$result->part_exam_motion
            .'" data-part_exam_dermatology="' .$result->part_exam_dermatology
            .'" data-part_exam_stomatology="' .$result->part_exam_stomatology
            .'" data-part_exam_lower_jaw="' .$result->part_exam_lower_jaw
            .'" data-part_exam_upper_jaw="' .$result->part_exam_upper_jaw
            .'" data-part_exam_ear="' .$result->part_exam_ear
            .'" data-part_exam_nose="' .$result->part_exam_nose
            .'" data-part_exam_throat="' .$result->part_exam_throat
            .'" data-part_exam_ear_right_normal="' .$result->part_exam_ear_right_normal
            .'" data-part_exam_ear_right_whisper="' .$result->part_exam_ear_right_whisper
            .'" data-part_exam_ear_left_normal="' .$result->part_exam_ear_left_normal
            .'" data-part_exam_ear_left_whisper="' .$result->part_exam_ear_left_whisper
            .'" data-part_exam_horizontal_sight="' .$result->part_exam_horizontal_sight
            .'" data-part_exam_vertical_sight="' .$result->part_exam_vertical_sight
            .'" data-part_exam_eye_blind_color="' .$result->part_exam_eye_blind_color
            .'" data-part_exam_eye="' .$result->part_exam_eye
            .'" data-part_exam_eye_tension_left="' .$result->part_exam_eye_tension_left
            .'" data-part_exam_eye_tension_right="' .$result->part_exam_eye_tension_right
            .'" data-part_exam_eyesight_left="' .$result->part_exam_eyesight_left
            .'" data-part_exam_eyesight_right="' .$result->part_exam_eyesight_right
            .'" data-part_exam_eyesight_glass_left="' .$result->part_exam_eyesight_glass_left
            .'" data-part_exam_eyesight_glass_right="' .$result->part_exam_eyesight_glass_right
            .'" data-part_exam_obstetric="' .$result->part_exam_obstetric
            .'" data-service_req_note="' .$result->service_req_note
            .'" data-treatment_instruction="' .$result->treatment_instruction
            .'" data-health_exam_rank_id="' .$result->health_exam_rank_id
            .'" data-service_req_stt_id="' .$result->service_req_stt_id 
            .'" data-treatment_id="' .$result->treatment_id
            .'" data-tdl_patient_avatar_url="' .$result->tdl_patient_avatar_url
            .'" data-subclinical="' .$result->subclinical
            .'" data-id="' .$result->id .'"><span class="glyphicon glyphicon-pencil"></span> Tổng kết</a>';
            $rtnData = $rtnData .'<a href="' .route('treatment-result.search',['treatment_code'=>$result->tdl_treatment_code]) .'" class="btn btn-sm btn-success" target="_blank">
                <span class="glyphicon glyphicon-eye-open"></span> EMR</a>';
            return $rtnData;
        })
        ->rawColumns(['service_req_stt_name','action','tdl_patient_phone'])
        ->toJson();
    }

   /*
        id
        weight
        height
        blood_pressure_max
        blood_pressure_min
        pulse
        note: phan loai the luc
    */
    public function khamtheluc(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        if (!$request->dhst_id) {
            try {
                $service_req = DB::connection('HISPro')
                ->table('his_service_req')
                ->where('id', $request->id)
                ->first();

                DB::connection('HISPro')
                ->table('his_dhst')
                ->insert([
                    'creator' => $service_req->creator,
                    'modifier' => $service_req->modifier,
                    'app_creator' => $service_req->app_creator,
                    'app_modifier' => $service_req->app_modifier,
                    'is_active' => $service_req->is_active,
                    'is_delete' => $service_req->is_delete,
                    'treatment_id' => $service_req->treatment_id,
                    'execute_room_id' => $service_req->execute_room_id,
                    'execute_department_id' => $service_req->execute_department_id,
                    'execute_loginname' => $service_req->execute_loginname,
                    'execute_username' => $service_req->execute_username,
                    'execute_time' => $service_req->start_time,
                    'weight' => $request->weight,
                    'height' => $request->height,
                    'blood_pressure_max' => $request->blood_pressure_max,
                    'blood_pressure_min' => $request->blood_pressure_min,
                    'pulse' => $request->pulse,
                    'note' =>$request->note
                ]);

                $his_dhst = DB::connection('HISPro')
                ->table('his_dhst')
                ->where('treatment_id', $service_req->treatment_id)
                ->first();

                DB::connection('HISPro')
                ->table('his_service_req')
                ->where('id', $service_req->id)
                ->update(['dhst_id' => $his_dhst->id]);

                return ['maKetqua' => '200',
                    'noiDung' => 'Cập nhật dữ liệu thành công!!!']; 
            } catch (\Exception $e) {
                return ['maKetqua' => '500',
                    'noiDung' => $e->getMessage()];   
            }
        }

        try {
            DB::connection('HISPro')
            ->table('his_dhst')
            ->where('id', $request->dhst_id)
            ->update(['weight' => $request->weight,
                'height' => $request->height,
                'blood_pressure_max' => $request->blood_pressure_max,
                'blood_pressure_min' => $request->blood_pressure_min,
                'pulse' => $request->pulse,
                'note' =>$request->note
            ]);
            return ['maKetqua' => '200',
                'noiDung' => 'Cập nhật dữ liệu thành công!!!'];            
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
    }

    /*
        id
        pathological_history
        part_exam
        part_exam_circulation
        part_exam_respiratory
        part_exam_digestion
        part_exam_kidney_urology
        part_exam_neurological
        part_exam_muscle_bone
        part_exam_oend
        part_exam_mental
        part_exam_nutrition
        part_exam_motion
        part_exam_dermatology
    */
    public function khamnoi(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['pathological_history' => $request->pathological_history,
                'part_exam' => $request->part_exam,
                'part_exam_circulation' => $request->part_exam_circulation,
                'part_exam_respiratory' => $request->part_exam_respiratory,
                'part_exam_digestion' => $request->part_exam_digestion,
                'part_exam_kidney_urology' => $request->part_exam_kidney_urology,
                'part_exam_neurological' => $request->part_exam_neurological,
                'part_exam_muscle_bone' => $request->part_exam_muscle_bone,
                'part_exam_oend' => $request->part_exam_oend,
                'part_exam_mental' => $request->part_exam_mental,
                'part_exam_nutrition' => $request->part_exam_nutrition,
                'part_exam_motion' => $request->part_exam_motion,
                'part_exam_dermatology' => $request->part_exam_dermatology
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
        part_exam_upper_jaw
        part_exam_lower_jaw
        part_exam_stomatology
    */
    public function khamrhm(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['part_exam_upper_jaw' => $request->part_exam_upper_jaw,
                'part_exam_lower_jaw' => $request->part_exam_lower_jaw,
                'part_exam_stomatology' => $request->part_exam_stomatology
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
        part_exam_ear
        part_exam_nose
        part_exam_throat
        part_exam_ear_right_normal
        part_exam_ear_right_whisper
        part_exam_ear_left_normal
        part_exam_ear_left_whisper
    */
    public function khamtmh(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['part_exam_ear' => $request->part_exam_ear,
                'part_exam_nose' => $request->part_exam_nose,
                'part_exam_throat' => $request->part_exam_throat,
                'part_exam_ear_right_normal' => $request->part_exam_ear_right_normal,
                'part_exam_ear_right_whisper' => $request->part_exam_ear_right_whisper,
                'part_exam_ear_left_normal' => $request->part_exam_ear_left_normal,
                'part_exam_ear_left_whisper' => $request->part_exam_ear_left_whisper
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
        part_exam_horizontal_sight: Thi truong ngang. 1: Binh thuong; 2: Han che
        part_exam_vertical_sight: Thi truong dung. 1: Binh thuong; 2: Han che
        part_exam_eye_blind_color: Sac giac. 1: Binh thuong; 2: Mu mau toan bo; 3: Mu mau do; 4: Mu mau xanh la; 5: Mu mau vang; 
        6: Mu mau do & xanh; 7: Mu mau do & vang; 8: Mu mau xanh & vang; 9: Mu mau & xanh & vang
        part_exam_eye: kham mat
        part_exam_eye_tension_left: Nhan ap mat trai
        part_exam_eye_tension_right: Nhan ap mat phai
        part_exam_eyesight_left: Thi luc mat trai
        part_exam_eyesight_right: Thi luc mat phai
        part_exam_eyesight_glass_left: Thi luc mat trai co kinh
        part_exam_eyesight_glass_right: Thi luc mat phai co kinh

    */
    public function khammat(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
            try {
                DB::connection('HISPro')
                ->table('his_service_req')
                ->where('id', $request->id)
                ->update(['part_exam_horizontal_sight' => $request->part_exam_horizontal_sight,
                    'part_exam_vertical_sight' => $request->part_exam_vertical_sight,
                    'part_exam_eye_blind_color' => $request->part_exam_eye_blind_color,
                    'part_exam_eye' => $request->part_exam_eye,
                    'part_exam_eye_tension_left' => $request->part_exam_eye_tension_left,
                    'part_exam_eye_tension_right' => $request->part_exam_eye_tension_right,
                    'part_exam_eyesight_left' => $request->part_exam_eyesight_left,
                    'part_exam_eyesight_right' => $request->part_exam_eyesight_right,
                    'part_exam_eyesight_glass_left' => $request->part_exam_eyesight_glass_left,
                    'part_exam_eyesight_glass_right' => $request->part_exam_eyesight_glass_right,
                ]);
            } catch (\Exception $e) {
                return ['maKetqua' => '500',
                    'noiDung' => $e->getMessage()];            
            }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
        part_exam_obstetric
    */
    public function khamsan(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['part_exam_obstetric' => $request->part_exam_obstetric
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
        health_exam_rank_id
        service_req_note
    */
    public function khamtongket(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }

        switch ($request->service_req_stt_id) {
            case 1:
                return ['maKetqua' => '400',
                    'noiDung' => 'Chưa thực hiện tiếp đón!!!'];
                break;
            case 3:
                return ['maKetqua' => '400',
                    'noiDung' => 'Đã tổng kết KSK!!!'];
                break;            
            default:
                // code...
                break;
        }

        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['note' => $request->service_req_note ? $request->service_req_note : null,
                'treatment_instruction' => $request->treatment_instruction,
                'health_exam_rank_id' =>  $request->health_exam_rank_id ? $request->health_exam_rank_id : null,
                'service_req_stt_id' => 3,
                'subclinical' => $request->subclinical
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    public function exportXLS(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $ksk_contract = $request->input('ksk_contract');
        $service_req_stt = $request->input('service_req_stt');

        return \Excel::download(new KSKExport($date_from, $date_to, $ksk_contract, $service_req_stt), date('YmdHi', strtotime(now())) . '_ksk.xlsx');
    }

    /*
        id
    */
    public function tiepdon(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        } 

        if (!$request->id) {
            return ['maKetqua' => '500',
                'noiDung' => 'Có lỗi trong quá trình xử lý!!!'];
        }
        
        try {
            $cmnd_number = null;
            $cccd_number = null;
            if (strlen($request->giay_tuy_than) < 12) {
                $cmnd_number = $request->giay_tuy_than;
            } else {
                $cccd_number = $request->giay_tuy_than;
            }

            $service_req = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->first();

            $url = $service_req->tdl_patient_code;
            $url = $url . '_AVATAR.jpeg';
            $url = '\Upload\MOS\Patient\\' . $url;

            if (str_contains($request->img,'data:image/jpeg;base64')) {
                $img = str_replace('data:image/jpeg;base64,', '', $request->img);
                $img = str_replace(' ','+', $img);
                $data = base64_decode($img);
                Storage::disk('emr')->put($url, $data);
            }

            DB::connection('HISPro')
            ->table('his_patient')
            ->where('id', $service_req->tdl_patient_id)
            ->update(['avatar_url' => $url,
                'address' => $request->address,
                'ht_address' => $request->ht_address,
                'phone' => $request->phone,
                'work_place_id' => $request->work_place_id ? $request->work_place_id : null,
                'work_place' => $request->work_place,
                'cmnd_number' => $cmnd_number,
                'cccd_number' => $cccd_number
            ]);       

            DB::connection('HISPro')
            ->table('his_treatment')
            ->where('id', $service_req->treatment_id)
            ->update(['tdl_patient_avatar_url' => $url,
                'hospitalization_reason' => $request->hospitalization_reason,
            ]);
            
            switch ($service_req->service_req_stt_id) {
                case 1:
                    DB::connection('HISPro')
                    ->table('his_service_req')
                    ->where('id', $request->id)
                    ->update(['service_req_stt_id' => 2,
                        'execute_loginname' => $service_req->request_loginname,
                        'execute_username' =>  $service_req->request_username,
                        'icd_code' => 'Z00.0',
                        'icd_name' => 'Khám sức khỏe tổng quát',
                        'pathological_process' => 'KSK',
                        'hospitalization_reason' => $request->hospitalization_reason,
                        'start_time' => date('YmdHis')
                    ]);
                    break;           
                default:
                    // code...
                    break;
            }

      
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];                
        }


        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }

    /*
        id
    */
    public function kqcls(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        if (!$request->id) {
            return ['maKetqua' => '500',
                'noiDung' => 'Có lỗi trong quá trình xử lý!!!'];
        }
        
        try {
            $service_req = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
            ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
            ->select('his_service_req_type.service_req_type_name', 'his_service_req_stt.service_req_stt_name')
            ->where('treatment_id', $request->id)
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->where('his_service_req.service_req_type_id', '<>', 1)
            ->get();

            $res = '';
            foreach ($service_req as $key => $value) {
                $idx = $key+1;
                $res = $res .'<tr>';
                $res = $res .'<td>' .$idx .'</td>';
                $res = $res .'<td>' .$value->service_req_type_name .'</td>';
                $res = $res .'<td>' .$value->service_req_stt_name .'</td>';
                $res = $res .'</tr>';
            }      
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];                
        }

        return $res;
    }

    public function downloadAvatar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa có ảnh chụp']; 
        }
        try {
            $rtn = Storage::disk('emr')->get($request->id);
            Storage::disk('public')->put($request->id, $rtn);            
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];   
        }
        return url('storage' .$request->id);   
    }

    public function getPatient(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Không thấy thông tin!!!']; 
        }

        try {
            $service_req = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->first();

            $patient = DB::connection('HISPro')
            ->table('his_patient')
            ->where('id', $service_req->tdl_patient_id)
            ->selectRaw('address,vir_address,ht_address,vir_ht_address,phone,
                work_place_id,work_place,cmnd_number||cccd_number as giay_tuy_than')
            ->first();

        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];   
        }
        return ['address' => $patient->address,
            'vir_address' => $patient->vir_address,
            'ht_address' => $patient->ht_address,
            'vir_ht_address' => $patient->vir_ht_address,
            'phone' => $patient->phone,
            'work_place_id' => $patient->work_place_id,
            'work_place' => $patient->work_place,
            'giay_tuy_than' => $patient->giay_tuy_than,
            'hospitalization_reason' => $service_req->hospitalization_reason,
        ];   
    }

    public function checkemr(Request $request)
    {
        return view('khth.ksk.check-emr');
    }

    public function getCheckEmr(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        try {
            $ParamNgay = $this->ParamNgay($request);
            $date_from = date_format(date_create($ParamNgay['tu_ngay']),'Ymd000000');
            $date_to = date_format(date_create($ParamNgay['den_ngay']),'Ymd235959');
            $execute_room_id = [58,126,642];

            $model = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
            ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
            ->select('his_service_req.tdl_treatment_code','his_service_req.num_order',
                'his_service_req.tdl_patient_name','his_service_req_stt.service_req_stt_name',
                'his_service_req.tdl_patient_dob', 'his_service_req.tdl_patient_gender_name'
            )
            ->where('his_service_req.intruction_time', '>=', $date_from)
            ->where('his_service_req.intruction_time', '<=', $date_to)
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->where('his_service_req.service_req_type_id', 1)
            ->whereIn('his_service_req.execute_room_id', $execute_room_id)
            ->orderBy('his_service_req.num_order')
            ->skip($request->get('scroll'))
            ->take(500);

            if ($request->get('hop_dong')) {
                $model = $model->whereIn('his_treatment.tdl_ksk_contract_id', $request->get('hop_dong'));
            }
            if ($request->get('trang_thai')) {
                $model = $model->whereIn('his_service_req.service_req_stt_id', $request->get('trang_thai'));
            }

            $rtn = $model->get();

            $emr_doc = DB::connection('EMR_RS')
                ->table('emr_document')
                ->where('emr_document.is_delete', 0)
                ->where('document_type_id', 160)
                ->whereIn('emr_document.treatment_code', $model->pluck('tdl_treatment_code'))
                ->get();

            $scroll = count($rtn) ? count($rtn) : 0;
            $data = [];
            foreach ($rtn as $key => $value) {
                $rtn_doc = $emr_doc->where('treatment_code', $value->tdl_treatment_code);
                $data_rtn = '';
                if (count($rtn_doc)) {
                    $check = '<span class="label label-success">Đã thiết lập</span>';
                } else {
                    $check = '<span class="label label-warning">Chưa thiết lập</span>';
                }

                foreach ($rtn_doc as $key_rtn_doc => $value_rtn_doc) {
                    if ($value_rtn_doc->un_signers) {
                        $data_rtn = $data_rtn .$value_rtn_doc->un_signers .',';
                    }
                }
                // $data[$key][] = ($request->get('scroll')+$key+1);
                // $data[$key][] = $value->num_order;
                // $data[$key][] = $value->service_req_stt_name;
                // $data[$key][] = $value->tdl_treatment_code;
                // $data[$key][] = $value->tdl_patient_name;
                // $data[$key][] = substr($value->tdl_patient_dob,0,4);
                // $data[$key][] = $value->tdl_patient_gender_name;
                // $data[$key][] = $check;
                // $data[$key][] = $data_rtn;
                // $data[$key][] = '<a href="' .route('treatment-result.search',['treatment_code'=>$value->tdl_treatment_code]) .'" class="btn btn-sm btn-primary" target="_blank">
                //     <span class="glyphicon glyphicon-eye-open"></span> Xem EMR</a>';
                $data[$key][] = '<tr>';
                $data[$key][] = '<td>' .($request->get('scroll')+$key+1) . '</td>';
                $data[$key][] = '<td>' .$value->num_order . '</td>';
                $data[$key][] = '<td>' .$value->service_req_stt_name . '</td>';
                $data[$key][] = '<td>' .$value->tdl_treatment_code . '</td>';
                $data[$key][] = '<td>' .$value->tdl_patient_name .'</td>';
                $data[$key][] = '<td>' .substr($value->tdl_patient_dob,0,4) .'</td>';
                $data[$key][] = '<td>' .$value->tdl_patient_gender_name .'</td>';
                $data[$key][] = '<td>' .$check .'</td>';
                $data[$key][] = '<td>' .$data_rtn .'</td>';
                $data[$key][] = '<td>' .'<a href="' .route('treatment-result.search',['treatment_code'=>$value->tdl_treatment_code]) .'" class="btn btn-sm btn-primary" target="_blank">
                    <span class="glyphicon glyphicon-eye-open"></span> Xem EMR</a>' . '</td>';
                $data[$key][] = '</tr>';
            }
            return [
                'scroll' => $scroll,
                'data' => $data
            ];
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];
        }
    }

    /*
        id
        next_treatment_instruction
    */
    public function tuvan(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        if (!$request->id) {
            return ['maKetqua' => '400',
                'noiDung' => 'Chưa hoàn thành việc tiếp đón!!!'];
        }
        try {
            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('id', $request->id)
            ->update(['next_treatment_instruction' => $request->next_treatment_instruction
            ]);
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];            
        }
        return ['maKetqua' => '200',
            'noiDung' => 'Cập nhật dữ liệu thành công!!!'];
    }
}   
