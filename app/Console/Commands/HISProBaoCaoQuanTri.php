<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\System\email_receive_report;

use DB;
use App\fetch_etc;

class HISProBaoCaoQuanTri extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baocaoquantri:day {date_from?} {date_to?} {--current} {--last_month} {--current_month} {--period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Daily Report email to Manager';

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
        $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
        $to_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
        $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

        if ($this->option('current')) {
            $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $to_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $title = 'Ngày ' . date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . ' (đến ' . date("H:i") . ')';
        }

        $from_date = $yesterday . '000000';
        $to_date = $yesterday . '235959';

        if ($this->option('last_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m")-1, "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))) . '235959';
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m")-1, "01", date("Y")));
            $to_date_str = date("t/m/Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
            $title = 'Tháng ' . date("m/Y", mktime(0, 0, 0, date("m")-1, "01", date("Y")));
        }

        if ($this->option('current_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m"), "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m") , "01", date("Y")));
            $to_date_str = date("t/m/Y", mktime(0, 0, 0, date("m") , date("d"), date("Y")));
            $title = 'Tháng ' . date("m/Y", mktime(0, 0, 0, date("m") , "01", date("Y"))) . ' (đến ngày ' . date("d/m") .')';
        }

        if ($this->argument('date_from') && $this->argument('date_to')) {
            $from_date = $this->argument('date_from');
            if (strlen($this->argument('date_from')) == 8) {
                $from_date = $this->argument('date_from') . '000000';
            }
            $to_date = $this->argument('date_to');
            if (strlen($this->argument('date_to')) == 8) {
                $to_date = $this->argument('date_to') . '235959';
            }
            $from_date_str = substr($from_date,6,2) .'/' .substr($from_date,4,2) .
                '/' .substr($from_date,0,4) .' ' .substr($from_date,8,2) .':' .substr($from_date,10,2);
            $to_date_str = substr($to_date,6,2) . '/' . substr($to_date,4,2) .
                '/' . substr($to_date,0,4) .' ' .substr($to_date,8,2) .':' .substr($to_date,10,2);
            $title = 'Từ ngày ' . $from_date_str . ' Đến ngày ' . $to_date_str;
        }

        /* Chốt số liệu tiêm chủng*/
        // if(!$this->option('current') && !$this->option('last_month') && !$this->option('current_month')) {
        //     $update_vaccin = DB::table('vaccinations')
        //     ->where('ngay_thang', '>=', $from_date)
        //     ->where('ngay_thang', '<=', $to_date)
        //     ->update(['trang_thai' => 1]);
        // }

        // $this->info('Thống kê dữ liệu tiêm chủng...');
        // /* Begin Thống kê tiêm chủng */
        // $count_vaccin = DB::table('vaccinations')
        // ->selectRaw('sum(mien_phi_thuong_tin) as mien_phi_thuong_tin,
        //     sum(dich_vu_thuong_tin) as dich_vu_thuong_tin,
        //     sum(mien_phi_thanh_tri) as mien_phi_thanh_tri,
        //     sum(dich_vu_thanh_tri) as dich_vu_thanh_tri,
        //     sum(mien_phi_noi_khac) as mien_phi_noi_khac,
        //     sum(dich_vu_noi_khac) as dich_vu_noi_khac')
        // ->where('ngay_thang', '>=', $from_date)
        // ->where('ngay_thang', '<=', $to_date)
        // ->get();
        $count_vaccin = [];
        /* End Thống kê tiêm chủng */

        $this->info('Doanh thu tổng...');
        $count_sere_serv_doanhthu = DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_type', 'his_sere_serv.tdl_service_type_id', '=', 'his_service_type.id')
        ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_type_id,service_type_name')
        ->where('tdl_intruction_time', '>=', $from_date)
        ->where('tdl_intruction_time', '<=', $to_date)
        ->where('his_sere_serv.is_delete', 0)
        ->groupBy('tdl_service_type_id','service_type_name')
        ->orderBy('thanh_tien','desc')
        ->get();
        $sum_sere_serv_doanhthu = $count_sere_serv_doanhthu->sum('thanh_tien');

        $this->info('Doanh thu khám...');
        $count_sere_serv_doanhthu_kham = DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_patient_type','his_sere_serv.patient_type_id','=','his_patient_type.id')
        ->join('his_treatment','his_sere_serv.tdl_treatment_id','=','his_treatment.id')
        ->join('his_branch','his_treatment.branch_id','=','his_branch.id')
        ->selectRaw('sum(amount*price) as thanh_tien,patient_type_name,branch_name')
        ->where('tdl_intruction_time', '>=', $from_date)
        ->where('tdl_intruction_time', '<=', $to_date)
        ->where('price','<>',0)
        ->where('his_sere_serv.is_delete', 0)
        ->where('tdl_service_req_type_id', config('__tech.service_req_type_kham'))
        ->groupBy('branch_name','patient_type_name')
        ->orderBy('thanh_tien','desc')
        ->get();
        $sum_sere_serv_doanhthu_kham = $count_sere_serv_doanhthu_kham->sum('thanh_tien');

        $this->info('Thống kê dịch bệnh...');
        /* Thống kê dịch bệnh */
        $a91 = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete', 0)
            ->where( function ($q) {
                $q->where('his_treatment.icd_code', 'like', '%A91%')
                ->orWhere('his_treatment.icd_code', 'like', '%A97%');
            })
            ->count();  

        $b08 = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete', 0)
            ->where('his_treatment.icd_code', 'like', '%B08%')
            ->count();     

        $b05 = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete', 0)
            ->where('his_treatment.icd_code', 'like', '%B05%')
            ->count();  

        $covid = DB::connection('HISPro')
        ->table('his_treatment')
        ->where('in_time', '>=', $from_date)
        ->where('in_time', '<=', $to_date)
        ->where('his_treatment.is_delete', 0)
        ->where( function ($q) {
            $q->where('his_treatment.icd_code', 'like', '%U07.1%')
            ->orWhere('his_treatment.icd_code', 'like', '%U07.2%')
            ->orWhere('his_treatment.icd_code', 'like', '%U08.9%')
            ->orWhere('his_treatment.icd_code', 'like', '%U09.9%')
            ->orWhere('his_treatment.icd_code', 'like', '%U10.9%')
            ->orWhere('his_treatment.icd_code', 'like', '%U11.9%')
            ->orWhere('his_treatment.icd_code', 'like', '%U12.9%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U07.1%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U07.2%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U08.9%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U09.9%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U10.9%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U11.9%')
            ->orWhere('his_treatment.icd_sub_code', 'like', '%U12.9%');
        })
        ->count();

        //dd($covid_khac);
        /* End Thống kê dịch bệnh*/

        $this->info('Thống kê bệnh nhân cấp cứu...');
        // Thống kê bệnh nhân cấp cứu
        $emergency = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
            ->leftJoin('his_treatment_result', 'his_treatment_result.id', '=', 'his_treatment.treatment_result_id')
            ->leftJoin('his_treatment_end_type', 'his_treatment_end_type.id', '=', 'his_treatment.treatment_end_type_id')
            ->select(
                'his_treatment.is_pause',
                'his_treatment_type.treatment_type_name',
                'his_treatment_result.treatment_result_name',
                'his_treatment_end_type.treatment_end_type_name'
            )
            ->where('his_treatment.in_time', '>=', $from_date)
            ->where('his_treatment.in_time', '<=', $to_date)
            ->where('his_treatment.is_emergency', 1)
            ->where('his_treatment.is_delete', 0)
            ->get();
        // End Thống kê bệnh nhân cấp cứu

        $this->info('Thống kê hồ sơ điều trị...');
        $treatment_countbydate = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->selectRaw('count(*) as so_luong,branch_name')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_patient.create_time', '<', $from_date)
            ->where('his_treatment.is_delete',0)
            ->groupBy('branch_name')
            ->get();
        $sum_treatment_countbydate = $treatment_countbydate->sum('so_luong');

        $newpatient_countbydate = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->selectRaw('count(*) as so_luong,branch_name')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_patient.create_time', '>=', $from_date)
            ->where('his_patient.create_time', '<=', $to_date)
            ->where('his_patient.is_delete',0)
            ->groupBy('branch_name')
            ->get();
        $sum_newpatient_countbydate = $newpatient_countbydate->sum('so_luong');

        $count_treatmentendtype = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('count(*) as so_luong,treatment_end_type_id')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete',0)
            ->groupBy('treatment_end_type_id')
            ->orderBy('so_luong','desc')
            ->get();

        $count_treatmentendtype_null = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
            ->selectRaw('count(*) as so_luong,last_department_id,department_name')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('treatment_end_type_id', null)
            ->where('his_treatment.is_delete',0)
            ->groupBy('last_department_id','department_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_treatmentendtype_null = $count_treatmentendtype_null->sum('so_luong');

        $count_patienttype = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('count(*) as so_luong,tdl_patient_type_id')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete',0)
            ->groupBy('tdl_patient_type_id')
            ->orderBy('so_luong','desc')
            ->get();

        $count_treatment_type = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_treatment_type', 'his_treatment.tdl_treatment_type_id', '=', 'his_treatment_type.id')
            ->selectRaw('count(*) as so_luong,his_treatment_type.treatment_type_name')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('his_treatment.is_delete',0)
            ->groupBy('treatment_type_name')
            ->orderBy('so_luong','desc')
            ->get();

        $this->info('Thống kê BN điều trị nội trú...');

        $count_noitru_department = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
            ->selectRaw('count(*) as so_luong,last_department_id,department_name')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->where('his_treatment.is_delete',0)
            ->groupBy('last_department_id','department_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_noitru = $count_noitru_department->sum('so_luong');

        $count_noitru_bed_room = DB::connection('HISPro')
        ->table('his_treatment_bed_room')
        ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
        ->join('his_room','his_bed_room.room_id','=','his_room.id')
        ->join('his_department','his_room.department_id','=','his_department.id')
        ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
        ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
        ->selectRaw('count(*) as so_luong,his_department.department_name')
        ->whereNull('his_treatment_bed_room.remove_time')
        ->whereNull('his_co_treatment.id')
        ->where('his_bed_room.is_active',1)
        ->where('his_room.is_active',1)
        ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
        ->where('his_treatment_bed_room.is_delete',0)
        ->where( function($q) use ($to_date) {
            $q->whereNull('out_time')
            ->orWhere('out_time', '>', $to_date);
        })
        ->groupBy('his_department.department_name')
        ->orderBy('so_luong','desc')
        ->get();

        $sum_noitru_bed_room = $count_noitru_bed_room->sum('so_luong');

        //->whereIn('tdl_hein_medi_org_code', explode(',', getParam('cskcb_dung_tuyen')->param_value))//
        $this->info('Thống kê chuyển viện...');

        $count_treatmentendtype_cv = DB::connection('HISPro')
            ->table('his_treatment')
            ->leftJoin('his_tran_pati_tech', 'his_treatment.tran_pati_tech_id', '=', 'his_tran_pati_tech.id')
            ->selectRaw('1 as so_luong, his_treatment.icd_code, his_treatment.icd_name, his_tran_pati_tech.tran_pati_tech_name')
            ->selectRaw('case when doctor_username is not null then doctor_username else end_username end as doctor_username')
            ->where('in_time', '>=', $from_date)
            ->where('in_time', '<=', $to_date)
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
            ->where('his_treatment.is_delete',0)
            //->groupBy('icd_code','icd_name','doctor_username')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_cv = $count_treatmentendtype_cv->sum('so_luong');

        $this->info('Thống kê dịch vụ kỹ thuật...');

        $count_sere_serv_dvkt = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req_type', 'his_sere_serv.tdl_service_req_type_id', '=', 'his_service_req_type.id')
            ->selectRaw('sum(amount) as so_luong,tdl_service_req_type_id,service_req_type_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->whereIn('tdl_service_req_type_id', explode(",", config('__tech.tdl_service_req_type_id_dvkt')))
            ->groupBy('tdl_service_req_type_id','service_req_type_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_dvkt = $count_sere_serv_dvkt->sum('so_luong');

        $this->info('Thống kê dịch vụ khám...');

        $count_sere_serv_kham = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_req_type_id', config('__tech.service_req_type_kham'))
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_kham = $count_sere_serv_kham->sum('so_luong');

        $count_kham_kotien = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,tdl_service_name,his_service_req.execute_username,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('price', 0)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_req_type_id', config('__tech.service_req_type_kham'))
            ->whereNotNull('tdl_is_main_exam')
            ->whereNotIn('patient_type_id', explode(',', config('__tech.patient_type_ko_tinh_tien_dvkt')))
            ->groupBy('tdl_service_name','his_service_req.execute_username','his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_kham_kotien = $count_kham_kotien->sum('so_luong');

        $this->info('Thống kê dịch vụ xét nghiệm...');

        $count_xetnghiem = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_type_id', config('__tech.service_req_type_xn'))
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_xetnghiem = $count_xetnghiem->sum('so_luong');

        $this->info('Thống kê dịch vụ CĐHA...');

        $count_cdha = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_type_id', config('__tech.service_req_type_cdha'))
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_cdha = $count_cdha->sum('so_luong');

        $count_sa = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_type_id', config('__tech.service_req_type_sa'))
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_sa = $count_sa->sum('so_luong');

        $count_ns = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('tdl_service_type_id', 9)
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();

        $this->info('Thống kê cảnh báo DVKT không tính tiền ...');

        $count_dvkt_kotien = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
            ->join('his_execute_room', 'his_sere_serv.tdl_execute_room_id', '=', 'his_execute_room.room_id')
            ->selectRaw('sum(amount) as so_luong,tdl_service_name,his_service_req.execute_username,his_execute_room.execute_room_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete',0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->where('price', 0)
            ->whereIn('tdl_service_req_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt_ko_kham')))
            ->whereNotIn('patient_type_id', explode(',', config('__tech.patient_type_ko_tinh_tien_dvkt')))
            ->groupBy('tdl_service_name','his_service_req.execute_username','his_execute_room.execute_room_name')
            ->orderBy('so_luong','desc')
            ->get();
        $sum_dvkt_kotien = $count_dvkt_kotien->sum('so_luong');

        $emails = email_receive_report::where('active', 1)
            ->where('bcaoqtri', 1);
            //->get();

        if ($this->option('period')) {
			$emails = $emails->where('period', 1);
        }
		
		$emails = $emails->get();
		
        $this->info('Đã tổng hợp xong dữ liệu, chuẩn bị gửi email...');

        foreach ($emails as $key => $value) {
            $this->info($value->email);
            $index = $key + 1;
            
            $i = 1;
            do {
                try {
                    Mail::send('templates.mail-bcaoqtri', array(
                        'from_date_str' => $from_date_str,
                        'to_date_str' => $to_date_str,
                        'title' => $title,
                        'is_month' => $this->option('current_month')||$this->option('last_month'),
                        'nsd' => $value,
                        'count_vaccin' => $count_vaccin,
                        'count_sere_serv_doanhthu' => $count_sere_serv_doanhthu,
                        'sum_sere_serv_doanhthu' => $sum_sere_serv_doanhthu,
                        'count_sere_serv_doanhthu_kham' => $count_sere_serv_doanhthu_kham,
                        'sum_sere_serv_doanhthu_kham' => $sum_sere_serv_doanhthu_kham,
                        'treatment_countbydate' => $treatment_countbydate,
                        'sum_treatment_countbydate' => $sum_treatment_countbydate,
                        'newpatient_countbydate' => $newpatient_countbydate,
                        'sum_newpatient_countbydate' => $sum_newpatient_countbydate,
                        'count_treatmentendtype' => $count_treatmentendtype,
                        'count_patienttype' => $count_patienttype,
                        'count_treatment_type' => $count_treatment_type,
                        'a91' => $a91,
                        'b08' => $b08,
                        'b05' => $b05,
                        'covid' => $covid,
                        'emergency' => $emergency,
                        'count_noitru_department' => $count_noitru_department,
                        'sum_noitru' => $sum_noitru,
                        'count_noitru_bed_room' => $count_noitru_bed_room,
                        'sum_noitru_bed_room' => $sum_noitru_bed_room,
                        'count_treatmentendtype_cv' => $count_treatmentendtype_cv,
                        'sum_cv' => $sum_cv,
                        'count_treatmentendtype_null' => $count_treatmentendtype_null,
                        'sum_treatmentendtype_null' => $sum_treatmentendtype_null,
                        'count_sere_serv_dvkt' => $count_sere_serv_dvkt,
                        'sum_dvkt' => $sum_dvkt,
                        'count_sere_serv_kham' => $count_sere_serv_kham,
                        'sum_kham' => $sum_kham,
                        'count_kham_kotien' => $count_kham_kotien,
                        'sum_kham_kotien' => $sum_kham_kotien,
                        'count_xetnghiem' => $count_xetnghiem,
                        'sum_xetnghiem' => $sum_xetnghiem,
                        'count_cdha' => $count_cdha,
                        'sum_cdha' => $sum_cdha,
                        'count_sa' => $count_sa,
                        'sum_sa' => $sum_sa,
                        'count_ns' => $count_ns,
                        'count_dvkt_kotien' => $count_dvkt_kotien,
                        'sum_dvkt_kotien' => $sum_dvkt_kotien),
                    function ($message) use ($title, $value) {        
                        $message->to($value->email);
                        $message->subject('Báo cáo quản trị: ' . $title . '; Ngày gửi: ' . date('d/m/Y H:i'));
                    });

                    $i = 10;       
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    $i++;
                }

            } while($i < 10);
        }

        if (count(Mail::failures())) {
            foreach (Mail::failures() as $key => $value) {
            }
        }

        $this->info($this->description);
    }
}
