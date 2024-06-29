<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use DB;
use App\Models\System\email_receive_report;

class HISProBaoCaoCacKhoa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baocaocackhoa:day {--yesterday} {--last_month} {--current_month} {from_date?} {to_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Báo cáo các khoa đã được gửi thành công';

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
        /* Báo cáo phục vụ các khoa cần thống kê */
        /* Các khoa phân biệt dựa vào cột bcao_khoa của bảng email_receive */
        /* Để cho dễ chỉnh sửa phân biệt như sau */
        /* 1: khoa Sản; 2: khoa Mắt Các khoa còn lại sẽ tăng dần */

        $date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

        if ($this->option('yesterday')) {
            $date = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
            $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . ' (đến ' . date("H:i") . ')';
        }

        $from_date = $date . '000000';
        $to_date = $date . '235959';

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

        if ($this->argument('from_date') && $this->argument('to_date')) {
            $from_date = $this->argument('from_date') . '000000';
            $to_date = $this->argument('to_date') . '235959';
            $title = 'Từ ngày: ' .$this->argument('from_date') .' đến ngày: ' .$this->argument('to_date');
        }

        /* I. Khoa Phụ Sản*/
        /* 1. Phòng khám phụ khoa */
        $sere_serv_pk_phu_khoa = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete', 0)
            ->where(function($q){
                $q->where('tdl_request_room_id',1522)
                    ->orWhere('tdl_execute_room_id',1522);
            })
            ->where('is_delete', 0)
            ->where('is_active', 1)
            ->groupBy('tdl_service_name')
            ->get();
        /* /I. Khoa Phụ Sản */

        /* II. Báo cáo khoa Mắt */
        $sere_serv_mat = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_name,tdl_service_code')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete', 0)
            ->where(function($q){
                $q->where('tdl_request_room_id', 62)
                    ->orWhere('tdl_execute_room_id', 62)
                    ->orWhere('tdl_execute_room_id', 236)
                    ->orWhere('tdl_execute_room_id', 236)
                    ->orWhere('tdl_execute_room_id', 117)
                    ->orWhere('tdl_execute_room_id', 117)
                    ->orWhere('tdl_execute_department_id', 50)
                    ->orWhere('tdl_request_department_id', 50);
            })
            ->where('is_delete', 0)
            ->where('is_active', 1)
            ->whereIn('tdl_service_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt')))
            ->groupBy('tdl_service_code','tdl_service_name')
            ->get();
        /* End II. */

        /* Process gửi email */
        $emails = email_receive_report::where('active', 1)
            ->whereNotNull('bcao_khoa')
            ->get();

        foreach ($emails as $key => $value) {
            $i = 1;
            do {
                try {
                    Mail::send('templates.mail-bcaockhoa', array('title' => $title,
                        'nsd' => $value,
                        'sere_serv_pk_phu_khoa' => $sere_serv_pk_phu_khoa,
                        'sere_serv_mat' => $sere_serv_mat,
                        ),
                    function ($message) use ($title, $value) {        
                        $message->to($value->email);
                        $message->subject('Báo cáo khoa - ' . $title . '; Ngày gửi: ' . date('d/m/Y H:i'));
                    });                   
                    $i = 10;
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    $i++;
                }
            } while ($i < 10);
        }
        /* End */

        $this->info($this->description);
    }
}
