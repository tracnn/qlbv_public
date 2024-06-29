<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use DB;

class LISRSDayKQBN extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daykqbn:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi kết quả XN cho BN';

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

        $ds_send_mail = DB::table('patient_send_mails')
            ->select('service_req_code')
            ->where('intruction_time', '>=', $from_date)
            ->where('intruction_time', '<=', $to_date)
            ->get();

        $ds_bn_nhan_email = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_patient', 'his_patient.patient_code', '=', 'his_service_req.tdl_patient_code')
            ->select('service_req_code','email',
                'his_patient.patient_code',
                'his_patient.vir_patient_name',
                'his_service_req.intruction_time')
            ->where('intruction_time', '>=', $from_date)
            ->where('intruction_time', '<=', $to_date)
            ->where('service_req_type_id', 2)
            ->where('service_req_stt_id', 3)
            ->whereNotIn('his_service_req.service_req_code', $ds_send_mail->pluck('service_req_code'))
            ->whereNotNull('his_patient.email')
            ->get();

        $lis_results = DB::connection('LIS_RS')
            ->table('lis_sample')
            ->join('lis_sample_service', 'lis_sample_service.sample_id', '=', 'lis_sample.id')
            ->join('lis_result', 'lis_result.sample_service_id', '=', 'lis_sample_service.id')
            ->select('lis_sample.service_req_code',
                'lis_sample_service.service_name',
                'lis_sample.patient_code',
                'lis_result.test_index_name',
                'lis_result.test_index_unit_symbol',
                'lis_result.value',
                'lis_result.description')
            ->whereIn('lis_sample.service_req_code', $ds_bn_nhan_email->pluck(['service_req_code']))
            ->get();

        $ds_bn = $ds_bn_nhan_email->groupBy('patient_code');

        foreach ($ds_bn as $key => $value) {
            $this->info($value->service_req_code);

            $result = $lis_results->where('patient_code', $value[0]->patient_code)
                ->groupBy('service_name');

            Mail::send('templates.mail-sendkqxn', array('patient_name' => $value[0]->vir_patient_name,
                'result' => $result),
            function ($message) use ($value) {
                $message->to($value[0]->email);
                $message->subject('Kết quả xét nghiệm - Ngày gửi: ' . date('d/m/Y H:i'));
            });

            foreach ($value as $key_v => $value_v) {
                DB::table('patient_send_mails')->insert(
                    ['service_req_code' => $value_v->service_req_code, 
                    'intruction_time' => $value_v->intruction_time]
                );
            }
        }
    }
}
