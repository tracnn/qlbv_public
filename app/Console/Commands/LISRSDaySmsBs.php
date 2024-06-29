<?php

namespace App\Console\Commands;

use DB;
use App\SendSmsXN;

use Illuminate\Console\Command;

class LISRSDaySmsBs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daysmsbs:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đẩy sms cho BS khi có kết quả XN';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

        $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000';
        $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';

        $ds_user = explode(',', preg_replace('/\s+/', '', getParam('ds_bs_nhan_sms_xn')->param_value));
        $ds_khoa = explode(',', preg_replace('/\s+/', '', getParam('ds_khoa_nhan_sms_xn')->param_value));

        $model_user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->select('loginname', 'email', 'mobile')
            ->whereNotNull('mobile')
            ->whereIn('loginname', $ds_user)
            ->get();

        $ds_da_gui_sms = DB::table('patient_send_sms')
            ->select('service_req_code')
            ->where('intruction_time', '>=', $from_date)
            ->where('intruction_time', '<=', $to_date)
            ->get();

        $ds_ho_so_gui_sms = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_department', 'his_service_req.request_department_id', '=', 'his_department.id')
            ->join('his_service_req_type', 'his_service_req.service_req_type_id', '=', 'his_service_req_type.id')
            ->select('tdl_treatment_code',
                'service_req_stt_id',
                'service_req_code',
                'intruction_time',
                'request_loginname',
                'service_req_type_code',
                'tdl_patient_name'
            )
            ->where('intruction_time', '>=', $from_date)
            ->where('intruction_time', '<=', $to_date)
            ->where('service_req_stt_id', 3)
            ->whereIn('service_req_type_id', explode(',', config('__tech.service_req_type_cls')))
            ->whereIn('his_department.department_code', $ds_khoa)
            ->whereIn('his_service_req.request_loginname', $ds_user)
            ->whereNotIn('his_service_req.service_req_code', $ds_da_gui_sms->pluck('service_req_code'))
            ->get();
        $ds_ho_so = $ds_ho_so_gui_sms->groupBy('request_loginname');
        //dd($ds_da_gui_sms->pluck('service_req_code'));
        foreach ($ds_ho_so as $request_loginname => $ho_so) {
            $current_user = $model_user->where('loginname', $request_loginname)
                ->first();
            $phone = $current_user->mobile;
            $this->info($request_loginname . ' ' .$phone);
            $content = 'Đã có KQ: ';
            foreach ($ho_so as $key => $value) {
                $content = $content .$value->service_req_type_code;
                $content = $content .' - ' .$value->tdl_patient_name .'; ';
                DB::table('patient_send_sms')->insert(
                    ['service_req_code' => $value->service_req_code, 
                    'intruction_time' => $value->intruction_time]
                );
            }
            $this->info($content);
            $content = substr($content, 0, strlen($content) - 2);
            $content = substr($content, 0, 160);
            $this->info($content);
            //SendSmsXN::sendSmsXN('0988795445', $content);
        };
    }

    // public function handle()
    // {

    //     $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000';
    //     $to_date = date("Ymt", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';

    //     $ds_user = explode(',', preg_replace('/\s+/', '', getParam('ds_bs_nhan_sms_xn')->param_value));
    //     $ds_khoa = explode(',', preg_replace('/\s+/', '', getParam('ds_khoa_nhan_sms_xn')->param_value));

    //     $model_user = DB::connection('ACS_RS')
    //         ->table('acs_user')
    //         ->select('loginname', 'email', 'mobile')
    //         ->whereNotNull('mobile')
    //         ->whereIn('loginname', $ds_user)
    //         ->get();

    //     $loginname = [];
    //     foreach ($model_user as $key => $value) {
    //         $loginname[] = $value->loginname;
    //     }

    //     $model = DB::connection('LIS_RS')
    //         ->table('lis_sample')
    //         ->where('lis_sample.intruction_time', '>=', $from_date)
    //         ->where('lis_sample.intruction_time', '<=', $to_date)
    //         ->whereIn('lis_sample.request_department_code', $ds_khoa)
    //         ->where('lis_sample.sample_stt_id', 4)
    //         ->whereIn('request_loginname', $loginname)
    //         ->whereNull('lis_sample.address')
    //         ->get();
    //     $model1 = DB::connection('LIS_RS')
    //         ->table('lis_sample')
    //         ->select('request_loginname','request_room_name')
    //         ->where('lis_sample.intruction_time', '>=', $from_date)
    //         ->where('lis_sample.intruction_time', '<=', $to_date)
    //         ->whereIn('lis_sample.request_department_code', $ds_khoa)
    //         ->where('lis_sample.sample_stt_id', 4)
    //         ->whereIn('request_loginname', $loginname)
    //         ->whereNull('lis_sample.address')
    //         ->groupBy('request_loginname','request_room_name')
    //         ->get();

    //     foreach ($model1 as $key => $value) {
    //         $current_user = $model_user->where('loginname', $value->request_loginname)
    //             ->first();
    //         if ($current_user) {
    //             if ($current_user->mobile) {
    //                 $phone = $current_user->mobile;

    //                 $result_xn = $model->where('request_loginname', $value->request_loginname)
    //                     ->where('request_room_name', $value->request_room_name);
                    
    //                 $content = 'Da co KQ XN: ';

    //                 $len_last = strlen($value->request_room_name);

    //                 $lis_sample_ids = [];

    //                 foreach ($result_xn as $key_xn => $value_xn) {
    //                     $lis_sample_ids[] = $value_xn->id;
    //                     $content = $content .$value_xn->last_name .' ' .$value_xn->first_name .', ';
    //                 }

    //                 $content = substr($content, 0, strlen($content) - 2);

    //                 $content = substr($content, 0, 157 - $len_last);

    //                 $content = substr($content .' * ' .$value->request_room_name, 0, 160);

    //                 // SendSmsXN::sendSmsXN($phone, $content);
    //                 // $update_xn = DB::connection('LIS_RS')
    //                 //     ->table('lis_sample')
    //                 //     ->where('request_loginname', $value->request_loginname)
    //                 //     ->whereIn('id', $lis_sample_ids)
    //                 //     ->update(['address' => 1]);
    //                 $this->info(print_r($lis_sample_ids));
    //             }
    //         }
    //     }
    //     $this->info($this->description);
    // }
}
