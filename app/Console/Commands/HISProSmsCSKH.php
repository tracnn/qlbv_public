<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\SendSmsXN;

class HISProSmsCSKH extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smscskh:day {--ngth} {--nhi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chăm sóc khách hàng';

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
        dd(SendSmsXN::sendSmsXN('0988795445', 'Test'));
        return;
        /* --Begin CSKH Khoa Ngoại TH */
        if ($this->option('ngth')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))) . '000000';
            $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))) . '235959';

            $ds_gui_sms = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_patient.patient_code', '=', 'his_treatment.tdl_patient_code')
                ->select('his_treatment.treatment_code',
                    'his_patient.vir_patient_name',
                    'his_patient.phone',
                    'his_treatment.out_time')
                ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
                ->where('treatment_end_type_id', '<>', config('__tech.treatment_end_type_cv'))
                ->where('end_department_id', 52)
                ->where('out_time', '>=', $from_date)
                ->where('out_time', '<=', $to_date)
                ->whereNotNull('his_patient.phone')
                ->get();
            foreach ($ds_gui_sms as $key => $value) {
                $content = 'Khoa Ngoại Tổng Hợp Thông Báo: BN ';
                $content = $content . $value->vir_patient_name .' ra viện ngày ';
                $content = $content . strtodate($value->out_time) . '. Bác Sỹ Dặn Dò: BN Uống Thuốc Theo Đơn, Tái Khám Theo Hẹn, Khám Lại Ngay Khi Có Bất Thường. Hotline: 0339881115 - 02462916416. Facebook: https://www.facebook.com/NGOAITONGHOPBVDKNN';
                $this->info($content);
                $this->info($value->phone);
                SendSmsXN::sendSmsXN($value->phone, $content);
                usleep(1000000);
            }
            
        }
        /* --End */

        /* --Begin CSKH Khoa Nhi */
        if ($this->option('nhi')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))) . '000000';
            $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))) . '235959';

            $ds_gui_sms = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_patient.patient_code', '=', 'his_treatment.tdl_patient_code')
                ->select('his_treatment.treatment_code',
                    'his_treatment.icd_name',
                    'his_treatment.icd_text',
                    'his_patient.vir_patient_name',
                    'his_patient.phone',
                    'his_treatment.out_time')
                ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
                ->where('treatment_end_type_id', '<>', config('__tech.treatment_end_type_cv'))
                ->where('end_department_id', 53)
                ->where('out_time', '>=', $from_date)
                ->where('out_time', '<=', $to_date)
                ->whereNotNull('his_patient.phone')
                ->get();
                
            foreach ($ds_gui_sms as $key => $value) {
                $content = 'Khoa Nhi BVĐKNN Thông Báo: BN ';
                $content = $content . $value->vir_patient_name .' ra viện ngày ';
                $content = $content . strtodate($value->out_time) . '. Chẩn đoán: ' . $value->icd_name .$value->icd_text . '. Nếu có bất thường cần tư vấn xin liên hệ Hotline: 0986984703';
                $this->info($content);
                $this->info($value->phone);
                SendSmsXN::sendSmsXN($value->phone, $content);
                usleep(1000000);
            }
            
        }
        /* --End */
    }
}
