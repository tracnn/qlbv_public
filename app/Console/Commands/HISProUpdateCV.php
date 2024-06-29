<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\BHYT\XML4;
use App\Models\BHYT\XML5;

class HISProUpdateCV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatecv:day {--yesterday} {--update} {--lock} {--unlock} {--restore} 
        {from_date?} {to_date?} {--repeat} {--deleteXML}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update transfer';

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
                $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
                $from_date = $yesterday . '000000';
                $to_date = $yesterday . '235959';

                if ($this->option('yesterday')) {
                    $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
                    $from_date = $yesterday . '000000';
                    $to_date = $yesterday . '235959';
                }

                if ($this->argument('from_date') && $this->argument('to_date')) {
                    $from_date = $this->argument('from_date') . '000000';
                    $to_date = $this->argument('to_date') . '235959';
                }
                $doctor_exclude = config('__tech.doctor');

                $doctor = array('binhvt-kcccd');//array('anhvt-kkb','sangt m-kn','binhvt-kcccd', 'duongdh-kcccd', 'hinhlc-kcccd','dungvv-kkb'); //,'dungvv-kkb'
                $creator = array('hni_nongnghiep');//array('thomtt-kcccd','hangtt-kkb','hunglm-cccd', 'binhvt-kcccd');

                $doctor_cc = [];//array('hinhlc-kcccd','duongdh-kcccd');
                $creator_cc = [];//array('thomtt-kcccd','hangtt-kkb','hunglm-cccd');

                $count = 0;
//49
                if($this->option('update')) {
                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('is_lock_hein')
                    ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    ->whereIn('doctor_loginname', $doctor)
                    ->whereIn('creator', $creator)
                    ->whereNotIn('fee_lock_loginname', ['tracnn'])
                    ->whereIn('fee_lock_department_id', [27,182])
                    ->whereIn('fee_lock_room_id', [54,49,1248])
                    ->update(['treatment_end_type_id' => 9]);

                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('is_lock_hein')
                    ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    ->whereIn('doctor_loginname', $doctor_cc)
                    ->whereIn('creator', $creator_cc)
                    ->whereNotIn('fee_lock_loginname', ['tracnn'])
                    ->where('fee_lock_department_id', 41)
                    ->update(['treatment_end_type_id' => 9]);

                    $treatments = DB::connection('HISPro')
                    ->table('his_treatment')
                    ->select('treatment_code')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotNull('is_lock_hein')
                    ->whereNotIn('fee_lock_loginname', ['tracnn'])
                    //->where('fee_lock_department_id', 27)
                    ->where('treatment_end_type_id', 9)
                    ->get();

                    //dd(count($treatments));
                    foreach (array_chunk($treatments->pluck('treatment_code')->toArray(), 1000) as $key => $value) {
                        DB::connection('HISPro')
                        ->table('his_service_req')
                        ->where('service_req_type_id', 1)
                        ->where('is_delete', 0)
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['is_delete' => 1]);

                        // DB::connection('HISPro')
                        // ->table('his_sere_serv')
                        // ->where('is_delete', 0)
                        // ->whereIn('creator', $creator)
                        // ->whereIn('tdl_treatment_code', $value)
                        // ->update(['is_delete' => 1]);

                        DB::connection('EMR_RS')
                        ->table('emr_treatment')
                        ->whereIn('treatment_code', $value)
                        ->where('treatment_end_type_name', '<>', 'Khác')
                        ->update([
                            'treatment_end_type_name' => 'Khác'
                        ]);

                        if($this->option('deleteXML')) {
                            /* Delete XML */
                            XML5::whereIn('MA_LK', $value)
                            ->delete();
                            XML4::whereIn('MA_LK', $value)
                            ->delete();
                            XML3::whereIn('MA_LK', $value)
                            ->delete();
                            XML2::whereIn('MA_LK', $value)
                            ->delete();
                            XML1::whereIn('MA_LK', $value)
                            ->delete();   
                            /* End Del XML */
                        }
                    }

                    // DB::connection('HISPro')
                    // ->statement('update his_treatment set in_time = in_time - 00030000000000,
                    //     in_date = in_date - 00030000000000,
                    //     out_time = out_time - 00030000000000,
                    //     out_date = out_date - 00030000000000 where create_time >= ' .
                    //     $from_date . ' and create_time <= ' . $to_date .
                    //     ' and fee_lock_time <= out_time and treatment_end_type_id = 9' .
                    //     ' and fee_lock_loginname <> \'' . implode(',', $doctor_exclude) .'\''
                    // );
                    
                    $count = count($treatments);
                }

                if($this->option('lock')) {
                    DB::connection('HISPro')
                    ->statement('update his_treatment set in_time = in_time - 00030000000000,
                        in_date = in_date - 00030000000000,
                        out_time = out_time - 00030000000000,
                        out_date = out_date - 00030000000000 where create_time >= ' .
                        $from_date .' and create_time <= ' . $to_date .
                        ' and fee_lock_time <= out_time and treatment_end_type_id = 9' .
                        'and fee_lock_loginname in (\'anhvt-kkb\',\'hinhlc-kcccd\',\'duongdh-kcccd\',\'binhvt-kcccd\',\'dungvv-kkb\')'
                    );
                }

                if($this->option('unlock')) {
                    DB::connection('HISPro')
                    ->statement('update his_treatment set in_time = in_time + 00030000000000,
                        in_date = in_date + 00030000000000,
                        out_time = out_time + 00030000000000,
                        out_date = out_date + 00030000000000 where create_time >= ' .
                        $from_date .' and create_time <= ' . $to_date .
                        ' and fee_lock_time > out_time and treatment_end_type_id = 9' .
                        'and fee_lock_loginname in (\'anhvt-kkb\',\'hinhlc-kcccd\',\'duongdh-kcccd\',\'binhvt-kcccd\',\'dungvv-kkb\')'
                    );
                }

                if($this->option('restore')) {
                    // DB::connection('HISPro')
                    // ->statement('update his_treatment set in_time = in_time + 00030000000000,
                    //     in_date = in_date + 00030000000000,
                    //     out_time = out_time + 00030000000000,
                    //     out_date = out_date + 00030000000000 where create_time >= ' .
                    //     $from_date . ' and create_time <= ' . $to_date .
                    //     ' and fee_lock_time > out_time and treatment_end_type_id = 9' .
                    //     ' and fee_lock_loginname <> \'' . implode(',', $doctor_exclude) .'\''
                    // );

                    $treatments = DB::connection('HISPro')
                    ->table('his_treatment')
                    ->select('treatment_code')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->whereNotIn('fee_lock_loginname', ['tracnn'])
                    //->where('fee_lock_department_id', 27)
                    ->where('treatment_end_type_id', 9)
                    ->get();
                    
                    foreach (array_chunk($treatments->pluck('treatment_code')->toArray(), 1000) as $key => $value) {
                        DB::connection('HISPro')
                        ->table('his_service_req')
                        ->where('service_req_type_id', 1)
                        ->where('is_delete', 1)
                        ->whereNull('exe_service_module_id')
                        ->whereIn('tdl_treatment_code', $value)
                        ->update(['exe_service_module_id' => 1, 'is_delete' => 0]);

                        // DB::connection('HISPro')
                        // ->table('his_sere_serv')
                        // ->where('tdl_service_type_id', 1)
                        // ->where('is_delete', 1)
                        // ->whereIn('tdl_treatment_code', $value)
                        // ->update(['is_delete' => 0]);
                    }

                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('create_time', '>=', $from_date)
                    ->where('create_time', '<=', $to_date)
                    ->whereNotNull('fee_lock_time')
                    ->where('treatment_end_type_id', 9)
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    ->whereIn('doctor_loginname', $doctor)
                    ->whereIn('creator', $creator)
                    //->where('fee_lock_department_id', 27)
                    ->update(['treatment_end_type_id' => config('__tech.treatment_end_type_cv')]);

                    // DB::connection('HISPro')
                    // ->table('his_treatment')
                    // ->where('create_time', '>=', $from_date)
                    // ->where('create_time', '<=', $to_date)
                    // ->whereNotNull('fee_lock_time')
                    // ->where('treatment_end_type_id', 9)
                    // ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
                    // ->whereIn('doctor_loginname', $doctor_cc)
                    // ->whereIn('creator', $creator_cc)
                    // ->whereNotIn('fee_lock_loginname', $doctor_exclude)
                    // ->where('fee_lock_department_id', 41)
                    // ->update(['treatment_end_type_id' => config('__tech.treatment_end_type_cv')]);

                    $count = count($treatments);
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
