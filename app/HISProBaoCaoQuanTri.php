<?php

namespace App;

use App\Models\HISPro\HIS_TREATMENT;
use App\Models\HISPro\HIS_PATIENT;
use App\Models\HISPro\HIS_DEPARTMENT;
use App\Models\HISPro\HIS_ICD;

use DB;

class HISProBaoCaoQuanTri
{
    public static function KiemTraTuDong($from_date,$to_date) {
        //return config('__tech.phong_tai_kham');
        /* Văn bản chờ ký */
        $model_documents = DB::connection('EMR_RS')
            ->table('emr_document')
            ->select('next_signer')
            ->whereNotNull('next_signer')
            ->where('is_delete', 0)
            ->where('create_date', '>=', $from_date)
            ->where('create_date', '<=', $to_date)
            ->get();
        //dd($model_documents);

        $model_user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->select('loginname', 'email', 'mobile')
            ->whereNotNull('mobile')
            ->whereIn('loginname', $model_documents->pluck('next_signer'))
            ->get();
            $content = 'Hệ thống EMR thông báo: Có văn bản đang chờ ký. Thời điểm gửi tin nhắn ' .now();
            //$phone = '0988795445';
            //SendSmsXN::sendSmsXN($phone, $content);
        foreach ($model_user as $key => $value) {
           $phone = $value->mobile;
           echo $phone .'</br>';
           //SendSmsXN::sendSmsXN($phone, $content);
        }
dd($model_user);

        /* Văn bản từ chối ký */
        $ds_user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->select('loginname', 'email', 'mobile')
            ->whereNotNull('mobile')
            ->get();

        $reject_documents = DB::connection('EMR_RS')
            ->table('emr_document')
            ->whereNotNull('rejecter')
            ->where('is_delete', 0)
            ->where('create_date', '>=', 20200922000000)
            ->where('create_date', '<=', 20200922235959)
            ->whereIn('request_loginname', $ds_user->pluck('loginname'))
            ->get();

        $model_user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->select('loginname', 'email', 'mobile')
            ->whereNotNull('mobile')
            ->whereIn('loginname', $reject_documents->pluck('request_loginname'))
            ->get();

        $reject_group = $reject_documents->groupBy('request_loginname');
        foreach ($reject_group as $request_loginname => $documents) {
            $current_user = $model_user->where('loginname', $request_loginname)
                ->first();
            $phone = $current_user->mobile;
            $content = 'Hệ thống EMR thông báo: Có văn bản từ chối ký. Mã điều trị: ';
            foreach ($documents as $key => $value) {
                $content = $content . $value->treatment_code . '; ';
                //$content = $content . ', ' . $value->document_name . ';';
            }
            echo $phone . '</br>';
            echo $content;
            //SendSmsXN::sendSmsXN($phone, $content);
        }
        /* End Văn bản từ chối ký */

        // $model = DB::connection('LIS_RS')
        //     ->table('lis_sample')
        //     ->join('ACS_RS.acs_user', 'acs_user.loginname', '=', 'lis_sample.request_loginname')
        //     ->where('lis_sample.intruction_time', '>=', $from_date)
        //     ->where('lis_sample.intruction_time', '<=', $to_date)
        //     ->where('lis_sample.request_department_code', 'K01')
        //     ->where('lis_sample.sample_stt_id', 4)
        //     ->whereNull('lis_sample.address')
        //     ->get();
        // dd($model);

        //*********************** Không xóa ************************//
        // $model1 = DB::connection('HISPro')
        //     ->table('his_treatment')
        //     ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
        //     ->selectRaw('count(*) as so_luong,0 as so_luong_tong,department_name')
        //     ->where('in_time', '>=', $yesterday . '000000')
        //     ->where('in_time', '<=', $yesterday . '235959')
        //     ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
        //     ->groupBy('department_name');
        // $model2 = DB::connection('HISPro')
        //     ->table('his_treatment_bed_room')
        //     ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
        //     ->join('his_room','his_bed_room.room_id','=','his_room.id')
        //     ->join('his_department','his_room.department_id','=','his_department.id')
        //     ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
        //     ->selectRaw('0 as so_luong,count(*) as so_luong_tong,his_department.department_name')
        //     ->whereNull('his_treatment_bed_room.remove_time')
        //     ->where('his_bed_room.is_active',1)
        //     ->where('his_room.is_active',1)
        //     ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
        //     ->groupBy('his_department.department_name')->union($model1)->get();


        // //dd($model2->groupBy('department_name'));
        // foreach ($model2->groupBy('department_name') as $key => $value) {
        //     echo $key . ': ';
        //     echo $value[0]->department_name . ': ';
        //     echo $value->sum('so_luong') . ': ';
        //     echo $value->sum('so_luong_tong');
        //     echo '<br>';
        // }
        //*********************** Không xóa ************************//
    }

}