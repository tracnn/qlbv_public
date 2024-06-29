<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use DB;
use App\BHYT;

use App\Models\CheckBHYT\check_treatment_record;
use App\Models\System\email_receive_report;
use App\Events\DemoPusherEvent;

class HISProKiemTraTheBHYT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kiemtrathebhyt:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra thẻ BHYT - BN đang điều trị';

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
        $count_noitru_bed_room = DB::connection('HISPro')
            ->table('his_treatment_bed_room')
            ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
            ->join('his_room','his_bed_room.room_id','=','his_room.id')
            ->join('his_department','his_room.department_id','=','his_department.id')
            ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
            ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
            ->selectRaw('his_treatment.treatment_code, his_treatment.tdl_hein_card_number, his_treatment.tdl_patient_name,
                his_treatment.tdl_patient_gender_id, his_treatment.tdl_hein_medi_org_code,
                his_treatment.tdl_hein_card_from_time, his_treatment.tdl_hein_card_to_time,
                his_room.department_id,
                his_treatment.tdl_patient_dob, his_department.department_name')
            ->whereNull('his_treatment_bed_room.remove_time')
            ->whereNull('his_co_treatment.id')
            ->where('his_bed_room.is_active',1)
            ->where('his_room.is_active',1)
            ->whereNotNull('his_treatment.tdl_hein_card_number')
            ->whereNotIn('his_treatment.tdl_treatment_type_id', explode(',',config('__tech.treatment_type_kham')))
            ->where('his_treatment_bed_room.is_delete',0)
            ->get();

        $expires_in = now();
        $status_401 = true;

        $bar = $this->output->createProgressBar(count($count_noitru_bed_room));
        $bar->setRedrawFrequency(5);
        $bar->start();

        foreach ($count_noitru_bed_room as $key => $value) {
            $index = $key + 1;
            if ((strtotime($expires_in) - strtotime(now()) <= 50) || $status_401) {
                $login_result = BHYT::loginBHYT();
                if ($login_result['maKetQua'] == '200') {
                    $status_401 = false;
                    $access_token = $login_result['APIKey']['access_token'];
                    $id_token = $login_result['APIKey']['id_token'];
                    $expires_in = $login_result['APIKey']['expires_in'];
                }
            }
            if ($login_result['maKetQua'] == '200') {

                $i = 1;
                do {
                    //$this->info($value->treatment_code);
                    try {
                        $result_tc = BHYT::checkInsuranceCard($value->tdl_hein_card_number, 
                            $value->tdl_patient_name, 
                            strtodate($value->tdl_patient_dob),
                            $access_token,$id_token);

                        $gioiTinh = 1;
                        if ($value->tdl_patient_gender_id == 1) {
                            $gioiTinh = 2;
                        }

                        $params = array('maThe' => $value->tdl_hein_card_number, 
                            'hoTen' => $value->tdl_patient_name, 
                            'ngaySinh' => strtodate($value->tdl_patient_dob), 
                            'gioiTinh' => $gioiTinh, 
                            'maCSKCB' => $value->tdl_hein_medi_org_code, 
                            'ngayBD' => strtodate($value->tdl_hein_card_from_time), 
                            'ngayKT' => strtodate($value->tdl_hein_card_to_time)
                        );

                        $result_kt_code = BHYT::lichSuKCB($params,
                            $result_tc['maDKBD'],
                            $result_tc['gioiTinh'],
                            $result_tc['gtTheTu'],
                            $result_tc['gtTheDen']);      

                        $i = 10;              
                    } catch (\Exception $e) {
                        $i++;
                    }
                } while($i < 10);
                
                if ($result_tc && $result_kt_code) {
                    check_treatment_record::where('active', 1)
                        ->where('treatment_code', $value->treatment_code)
                        ->update(['active' => 0]);

                    $model = new check_treatment_record;
                    $model->active = 1;
                    $model->treatment_code = $value->treatment_code;
                    $model->number = $value->tdl_hein_card_number;
                    $model->department_id = $value->department_id;
                    $model->department_name = $value->department_name;
                    $model->check_code = $result_kt_code;
                    $model->search_code = $result_tc['maKetQua'];
                    $model->old_number = $result_tc['maTheCu'];
                    $model->new_number = $result_tc['maTheMoi'];
                    $model->save();
                }
   
                if (($result_kt_code == '401') || ($result_tc['maKetQua'] == '401')) {
                    // $this->info($result_kt['maKetQua']);
                    // $this->info($result_tc['maKetQua']);                     
                    $status_401 = true;
                }

                // $this->info($value->treatment_code . ' - ' . $value->department_id . 
                //     ' - ' . $result_tc['maKetQua'] . ' - ' . $result_kt['maKetQua']);
                //dd($result_kt);
            } else {
                $status_401 = true;
            }
            $bar->advance();
        }
        $bar->finish();

        $result = check_treatment_record::where('active', 1)
            ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime(now())))
            ->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime(now())))
            ->whereNotNull('number')
            ->where('number', 'not like', '%KT%')
            ->where('check_code', '<>', '401')
            ->where('search_code', '<>', '401')
            ->where(function($q){
                $q->where('check_code', '<>', '00')
                    ->orWhere('search_code', '<>', '000');
            })
            ->whereNotNull('check_code')
            ->whereNotNull('search_code')
            ->get();

        $emails = email_receive_report::where('active', 1)
            ->where('bcaobhxh', 1)->get();

        foreach ($emails as $key => $value) {
            $index = $key + 1;
            $i = 1;
            do {
                try {
                    Mail::send('templates.mail-check-treatment-record', 
                        array('result' => $result,
                        'value' => $value),
                    function ($message) use ($value) {        
                        $message->to($value->email);
                        $message->subject('Hệ thống kiểm tra hồ sơ điều trị - Ngày: ' . date('d/m/Y H:i', strtotime(now())));
                    });    
                    $i = 10;            
                } catch (\Exception $e) {
                    $i++;
                }
            } while ($i < 10);
        }
        $this->info($this->description);
    }
}
