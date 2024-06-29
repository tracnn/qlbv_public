<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use DB;
use App\Models\System\email_receive_report;

class HISProBaoCaoDinhDuong extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baocaodinhduong:day {--current} {--last_month} {--current_month} {--next}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Báo cáo khoa dinh dưỡng';

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
        $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
        $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

        if ($this->option('current')) {
            $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . ' (đến ' . date("H:i") . ')';
        }

        if ($this->option('next')) {
            $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
            $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . ' (đến ' . date("H:i") . ')';
        }


        $from_date = $yesterday . '000000';
        $to_date = $yesterday . '235959';

        if ($this->option('last_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m")-1, "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))) . '235959';
            $title = 'Tháng ' . date("m/Y", mktime(0, 0, 0, date("m")-1, "01", date("Y")));
        }

        if ($this->option('current_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m"), "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';
            $title = 'Tháng ' . date("m/Y", mktime(0, 0, 0, date("m") , "01", date("Y"))) . ' (đến ngày ' . date("d/m") .')';
        }

        /* Khoa tiết chế dinh dưỡng */
        $count_dinh_duong = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_sere_serv', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->join('his_department', 'his_service_req.request_department_id', '=', 'his_department.id')
            ->join('his_ration_time', 'his_service_req.ration_time_id', '=', 'his_ration_time.id')
            ->selectRaw('his_department.department_name, 
                his_ration_time.ration_time_code,
                his_ration_time.ration_time_name,
                his_sere_serv.tdl_service_code,
                his_sere_serv.tdl_service_name,
                sum(amount) as quality')
            ->where('his_service_req.intruction_time', '>=', $from_date)
            ->where('his_service_req.intruction_time', '<=', $to_date)
            ->where('his_service_req.service_req_type_id', 17)
            ->groupBy('his_department.department_name' ,
                'his_ration_time.ration_time_code',
                'his_ration_time.ration_time_name',
                'his_sere_serv.tdl_service_code',
                'his_sere_serv.tdl_service_name')
            ->orderBy('his_department.department_name')
            ->orderBy('his_sere_serv.tdl_service_code')
            ->orderBy('his_ration_time.ration_time_code')
            ->get();

        $count_dinh_duong_tong = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_sere_serv', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->join('his_department', 'his_service_req.request_department_id', '=', 'his_department.id')
            ->join('his_ration_time', 'his_service_req.ration_time_id', '=', 'his_ration_time.id')
            ->selectRaw('his_sere_serv.tdl_service_code,
                his_sere_serv.tdl_service_name,
                sum(amount) as quality')
            ->where('his_service_req.intruction_time', '>=', $from_date)
            ->where('his_service_req.intruction_time', '<=', $to_date)
            ->where('his_service_req.service_req_type_id', 17)
            ->groupBy('his_sere_serv.tdl_service_code',
                'his_sere_serv.tdl_service_name')
            ->orderBy('his_sere_serv.tdl_service_code')
            ->get();

        $sum_dinh_duong = $count_dinh_duong->sum('quality');
        //dd($count_dinh_duong);

        /* Process gửi email */
        $emails = email_receive_report::where('active', 1)
            ->where(function($q){
                $q->where('dinh_duong', 1);
            })
            ->get();

        foreach ($emails as $key => $value) {
            Mail::send('templates.mail-bcaodduong', array('title' => $title,
                'nsd' => $value,
                'count_dinh_duong' => $count_dinh_duong,
                'sum_dinh_duong' => $sum_dinh_duong,
                'count_dinh_duong_tong' => $count_dinh_duong_tong,
                ),
            function ($message) use ($title, $value) {        
                $message->to($value->email);
                $message->subject('Báo cáo khoa dinh dưỡng: ' . $title . '; Ngày gửi: ' . date('d/m/Y H:i'));
            });

        }
        /* End */

        $this->info($this->description);
    }
}
