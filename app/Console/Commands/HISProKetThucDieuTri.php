<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use Session;
class HISProKetThucDieuTri extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ketthucdieutri:day {date_from?} {date_to?} {--yesterday} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động kết thúc điều trị';

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
        /*
            Tự động kết thúc điều trị BN
                1. Hồ sơ chưa kết thúc điều trị
                2. BN viện phí, KSK, Hợp đồng, Hợp đồng CLS
                3. Các y lệnh đã kết thúc
                4. Diện điều trị là khám
        */

        $date_str = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        if ($this->option('yesterday')) {
            $date_str = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
        }

        $from_date = $date_str . '000000';
        $to_date = $date_str . '235959';

        if ($this->argument('date_from') && $this->argument('date_to')) {
            $from_date = $this->argument('date_from') . '000000';
            $to_date = $this->argument('date_to') . '235959';
        }

        $DanhSachHoSo = DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_sere_serv', 'his_sere_serv.tdl_treatment_code', '=', 'his_treatment.treatment_code')
        ->selectRaw('his_treatment.treatment_code')
        ->where('in_time', '>=', $from_date)
        ->where('in_time', '<=', $to_date)
        ->whereNull('his_treatment.is_pause')
        ->where('his_treatment.tdl_treatment_type_id', 1)
        //->whereIn('his_treatment.tdl_patient_type_id',[43,62,142,102])
        ->groupBy('his_treatment.treatment_code')
        ->get();

        $bar = $this->output->createProgressBar(count($DanhSachHoSo));
        $bar->start();

        foreach ($DanhSachHoSo as $key => $value) {
            $shouldUpdate = false;
            
            if ($this->option('force')) {
                // Nếu có option --force, bỏ qua kiểm tra y lệnh
                $shouldUpdate = true;
            } else {
                // Kiểm tra y lệnh như bình thường
                $test_bn = DB::connection('HISPro')
                ->table('his_service_req')
                ->where('his_service_req.is_delete', 0)
                ->where('his_service_req.service_req_stt_id', '!=', 3)
                ->whereNotIn('his_service_req.service_req_type_id', [6,11])
                ->where('his_service_req.tdl_treatment_code', $value->treatment_code);
                
                if (!$test_bn->count()) {
                    $shouldUpdate = true;
                }
            }
            
            if ($shouldUpdate) {
                $this->info($value->treatment_code);
                $result = DB::connection('HISPro')
                ->table('his_treatment')
                ->where('treatment_code', $value->treatment_code)
                ->first();
                DB::connection('HISPro')
                ->table('his_treatment')
                ->where('treatment_code', $value->treatment_code)
                ->update(['out_time' => $result->in_time,
                    'treatment_result_id' => 3,
                    'treatment_end_type_id' => 5,
                    'is_pause' => 1]);
            }
            $bar->advance();
        }
        $bar->finish();

    }
}
