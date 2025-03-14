<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use DB;
use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\BHYT\XML4;
use App\Models\BHYT\XML5;
use App\Models\CheckBHYT\check_hein_card;
use App\Models\XmlErrorCheck;

class UpdateCV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatecv:admin {--yesterday} {--update} {--lock} {--unlock} {--restore} 
    {from_date?} {to_date?} {--repeat} {--updateSDA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer administrator';

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
        $repeat = $this->option('repeat') ? true : false;
        do {
            try {
                $creator = 'hni_nongnghiep';
                $doctor = 'tracnn';

                $creator_des = 'hni_nongnghiep';
                $creator_des_name = 'Tiếp đón thẻ KCB';
                $doctor_des = 'dungvv-kkb';
				$doctor_des_username = 'Vũ Việt Dũng';

                $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000';
                $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';

                if ($this->option('yesterday')) {
                    $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y"))) . '000000';
                    $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y"))) . '235959';
                }

                if ($this->argument('from_date') && $this->argument('to_date')) {
                    $from_date = $this->argument('from_date') . '000000';
                    $to_date = $this->argument('to_date') . '235959';
                }

                if ($this->option('updateSDA')) {
                    $ip_creator = '10.0.' . rand(4,7) . '.' . rand(51,255);
                    $ip_doctor = '10.0.' . rand(4,7) . '.' . rand(51,255);
					
					$this->info('SDA_RS');
                    DB::connection('SDA_RS')
                    ->table('sda_event_log')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->where('login_name', $doctor)
                    ->update(['login_name' => $doctor_des,
                        'ip' => $ip_doctor
                    ]);

                    DB::connection('SDA_RS')
                    ->table('sda_event_log')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->where('login_name', $creator)
                    ->update(['login_name' => $creator_des,
                        'ip' => $ip_creator
                    ]);
					
					$this->info('HISPro');
                    DB::connection('HISPro')
                    ->table('his_dhst')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->where('creator', $doctor)
                    ->update(['creator' => $doctor_des,
                        'modifier' => $doctor_des,
                        'execute_loginname' => $doctor_des
                    ]);

                    DB::connection('HISPro')
                    ->table('his_treatment_logging')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->where('loginname', $doctor)
                    ->update(['loginname' => $doctor_des
                    ]);
					$this->info('EMR_RS');
                    DB::connection('EMR_RS')
                    ->table('emr_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->where('creator', $creator)
                    ->update(['creator' => $creator_des,
                        'modifier' => $creator_des
                    ]);
                }

                DB::connection('HISPro')
                ->table('his_patient')
                ->where('create_time', '>=', $from_date)
                ->where('create_time', '<=', $to_date)
                ->where('creator', $creator)
                ->update(['creator' => $creator_des,
                    'modifier' => $doctor_des,
                ]);

                DB::connection('HISPro')
                ->table('his_treatment')
                ->where('create_time', '>=', $from_date)
                ->where('create_time', '<=', $to_date)
                ->where('creator', $creator)
                ->update(['creator' => $creator_des,
                    'modifier' => $doctor_des,
                ]);    
                
                $count = 0;

                if($this->option('update')) {
                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('is_lock_hein')
                    ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    ->where('fee_lock_loginname', $doctor)
                    ->update(['treatment_end_type_id' => 4,
                        'creator' => $creator_des,
                        'modifier' => $creator_des,
                        'doctor_loginname' => $doctor_des,
                        'end_loginname' => $doctor_des,
                        'end_username' => $doctor_des_username,
						'doctor_username' => $doctor_des_username,
                        'create_time' => DB::raw('in_time'),
                        'modify_time' => DB::raw('in_time')
                    ]);
                    
                    $treatments = DB::connection('HISPro')
                    ->table('his_treatment')
                    ->select('treatment_code')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('is_lock_hein')
                    ->whereNotNull('medi_org_code')
                    ->where('fee_lock_loginname', $doctor)
                    ->where('treatment_end_type_id', 4)
                    ->get();

                    foreach (array_chunk($treatments->pluck('treatment_code')->toArray(), 1000) as $key => $value) {
                        DB::connection('HISPro')
                        ->table('his_service_req')
                        ->where('service_req_type_id', 1)
                        ->where('is_delete', 0)
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['creator' => $creator_des,
                            'modifier' => $doctor_des,
                            'request_loginname' => $creator_des,
                            'request_username' => $creator_des_name,
                            'execute_loginname' => $doctor_des,
							'execute_username' => $doctor_des_username,
                            'is_delete' => 1
                        ]);
                        
                        DB::connection('HISPro')
                        ->table('his_sere_serv')
                        ->where('is_delete', 0)
                        ->where('creator', $creator)
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['creator' => $creator_des,
                            'modifier' => $doctor_des
                        ]);

                        DB::connection('EMR_RS')
                        ->table('emr_treatment')
                        ->whereIn('treatment_code', $value)
                        ->where('treatment_end_type_name', '<>', 'Cấp toa cho về')
                        ->update(['creator' => $creator_des,
                            'modifier' => $creator_des,
                            'treatment_end_type_name' => 'Cấp toa cho về',
                            'create_time' => DB::raw('in_time'),
                            'modify_time' => DB::raw('in_time')
                        ]);
                    }


                    $count = count($treatments);
                }
                
                if($this->option('lock')) {
                    DB::connection('HISPro')
                    ->statement('update his_treatment set in_time = in_time - 00030000000000,
                        in_date = in_date - 00030000000000,
                        out_time = out_time - 00030000000000,
                        out_date = out_date - 00030000000000 where create_time >= ' .
                        $from_date .' and create_time <= ' . $to_date .
                        ' and fee_lock_time <= out_time and treatment_end_type_id = 4 ' .
                        ' AND medi_org_code IS NOT NULL ' .
                        ' and fee_lock_loginname =\'' . $doctor .'\''
                    );
                }

                if($this->option('unlock')) {
                    DB::connection('HISPro')
                    ->statement('update his_treatment set in_time = in_time + 00030000000000,
                        in_date = in_date + 00030000000000,
                        out_time = out_time + 00030000000000,
                        out_date = out_date + 00030000000000 where create_time >= ' .
                        $from_date .' and create_time <= ' . $to_date .
                        ' and fee_lock_time <= out_time and treatment_end_type_id = 4 ' .
                        ' AND medi_org_code IS NOT NULL ' .
                        ' and fee_lock_loginname =\'' . $doctor .'\''
                    );
                }

                if($this->option('restore')) {
                    $treatments = DB::connection('HISPro')
                    ->table('his_treatment')
                    ->select('treatment_code')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->where('fee_lock_loginname', $doctor)
                    ->whereNotNull('medi_org_code')
                    ->where('treatment_end_type_id', 4)
                    ->get();

                    foreach (array_chunk($treatments->pluck('treatment_code')->toArray(), 1000) as $key => $value) {
                        DB::connection('HISPro')
                        ->table('his_service_req')
                        ->where('service_req_type_id', 1)
                        ->where('is_delete', 1)
                        ->whereNull('exe_service_module_id')
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['exe_service_module_id' => 1, 'is_delete' => 0]);
                        
                        DB::connection('HISPro')
                        ->table('his_sere_serv')
                        ->where('tdl_service_type_id', 1)
                        ->where('is_delete', 1)
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['is_delete' => 0]);
                    }

                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('medi_org_code')
                    ->where('treatment_end_type_id', 4)
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    ->where('fee_lock_loginname', $doctor)
                    ->update(['treatment_end_type_id' => config('__tech.treatment_end_type_cv')]);
                }

                $this->info($this->description .' ' .now() .': ' .$count);
                if ($repeat) {
                    sleep(186);
                }
            } catch (\Exception $e) {
                $this->info($e->getMessage());
            }
        } while ($repeat);
    }
}
