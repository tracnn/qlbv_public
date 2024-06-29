<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\System\email_receive_report;

use App\Models\HISPro\HIS_DEPARTMENT;
use App\Models\HISPro\HIS_ICD;

use DB;

class HISProBaoCaoAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baocaoadmin:day {--current} {--last_month} {--current_month} {--week} {from_date?} {to_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Daily Report email to Administrator';

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
		$hein_cards = 
		['GD4010130824198',
		'HT2010128628948',
		'DN4010125561496',
		'GD4010122726147',
		'DN4010131668575',
		'CK2010125538935',
		'GD4010125695710',
		'HT3010128634732',
		'GD4010125687106',
		'KC2010130967881',
		'GD4010120953642',
		'GD4010120872113',
		'GD4010125907830',
		'GD4013820331194',
		'CB2010121965867',
		'HD4010128319061',
		'GD4010123729946',
		'GD4010121723806',
		'GD4010124016662',
		'TS2010124038589',
		'GD4010129960997',
		'GD4010120525340',
		'GD4010125635571',
		'GD4010128353455',
		'KC2010125616633',
		'GD4010125949514',
		'TQ4979731188129',
		'GD4010122283471',
		'HT3010122524166',
		'GD4010125377294',
		'KC2010125740837',
		'DN4013422432396',
		'GD4010124123630',
		'DN4010130303312',
		'GD4010122858033',
		'GD4010128020852',
		'HS4010125500257',
		'GD4010125955779',
		'GD4010130815617',
		'GD4010120003650',
		'TC3010125814314',
		'GD4010121595533',
		'GD4010130677351',
		'HT3012596037244',
		'GD4010131149493',
		'GD4010122946480',
		'GD4010122684024',
		'GD4010122671188',
		'CB2010122269235',
		'GD4010123923123',
		'GD4010123998712',
		'GD4010130022994',
		'CK2010128207010',
		'GD4010121732286',
		'HC4012299059336',
		'HC4012299059337',
		'GD4010123912433',
		'GD4010124640314',
		'GD4010125977133',
		'KC4010124175409',
		'GD4010127941316',
		'GD4010124486027',
		'GD4010131548400',
		'GD4010128351958',
		'GD4010126008180',
		'DN4013622506496',
		'GD4010125763318',
		'HT2010128479082',
		'GD4010125878865',
		'GD4010130479326',
		'DN4010199036688',
		'GD4010126068154',
		'GD4010121934725',
		'GD4010125015196',
		'GD4010130512545',
		'HT2010128625457',
		'GD4010131223415',
		'GD4010130816927',
		'BT2010123863746',
		'MS4010128587881',
		'TQ4979794602422',
		'GD4010125306653',
		'GD4010125364569',
		'GD4010129026209',
		'HT2010125465486',
		'GD4010120829010',
		'HT3010130828685',
		'GD4010125961025',
		'DN4790116322180'];
		
        $doctor = config('__tech.doctor');

        $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
        $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
        $to_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

        if ($this->option('current')) {
            $yesterday = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $to_date_str = date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        }

        $from_date = $yesterday . '000000';
        $to_date = $yesterday . '235959';

        if ($this->option('last_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m")-1, "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))) . '235959';
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m")-1, "01", date("Y")));
            $to_date_str = date("t/m/Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
        }

        if ($this->option('current_month')) {
            $from_date = date("Ymd", mktime(0, 0, 0, date("m"), "01", date("Y"))) . '000000';
            $to_date = date("Ymt", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';
            $from_date_str = date("d/m/Y", mktime(0, 0, 0, date("m") , "01", date("Y")));
            $to_date_str = date("t/m/Y", mktime(0, 0, 0, date("m") , date("d"), date("Y")));
        }

        if ($this->option('week')) {
            $from_date = date("Ymd",strtotime(date("Y") . "W". date('W') . "1")) .'000000';
            $to_date = date("Ymd",strtotime(date("Y") . "W". date('W') . "5")) . '235959';
            $from_date_str = date("d/m/Y",strtotime(date("Y") . "W". date('W') . "1"));
            $to_date_str = date("d/m/Y",strtotime(date("Y") . "W". date('W') . "5"));
        }

        if ($this->argument('from_date') && $this->argument('to_date')) {
            $from_date = $this->argument('from_date') . '000000';
            $to_date = $this->argument('to_date') . '235959';
            $from_date_str = substr($from_date,6,2) .'/' .substr($from_date,4,2) .
                '/' .substr($from_date,0,4) .' ' .substr($from_date,8,2) .':' .substr($from_date,10,2);
            $to_date_str = substr($to_date,6,2) . '/' . substr($to_date,4,2) .
                '/' . substr($to_date,0,4) .' ' .substr($to_date,8,2) .':' .substr($to_date,10,2);
        }

        $ds_cv_all =  DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_branch','his_treatment.branch_id','=','his_branch.id')
            ->selectRaw('branch_name,count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('branch_name')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_all = $ds_cv_all->sum('so_luong');

        $ds_cv_all_bs =  DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('doctor_loginname,doctor_username,count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('doctor_loginname','doctor_username')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_all_bs = $ds_cv_all_bs->sum('so_luong');

        $ds_cv_all_nn =  DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('creator,count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('creator')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_all_nn = $ds_cv_all_nn->sum('so_luong');

        $ds_cv_noi =  DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_branch','his_treatment.branch_id','=','his_branch.id')
            ->selectRaw('branch_name,count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            ->whereNotIn('tdl_hein_medi_org_code', explode(',', getParam('cskcb_dung_tuyen')->param_value))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('branch_name')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_noi = $ds_cv_noi->sum('so_luong');

        $ds_cv_noi_bs =  DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('doctor_loginname,doctor_username, count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            ->whereNotIn('tdl_hein_medi_org_code', explode(',', getParam('cskcb_dung_tuyen')->param_value))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('doctor_loginname','doctor_username')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_noi_bs = $ds_cv_noi_bs->sum('so_luong');

        $ds_cv_noi_nn =  DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('creator, count(*) as so_luong')
            ->where('his_treatment.create_time', '>=', $from_date)
            ->where('his_treatment.create_time', '<=', $to_date)
            ->whereNotIn('fee_lock_loginname', $doctor)
            ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            ->whereNotIn('tdl_hein_medi_org_code', explode(',', getParam('cskcb_dung_tuyen')->param_value))
			->whereNotIn('tdl_hein_card_number', $hein_cards)
            ->groupBy('creator')
            ->orderBy('so_luong','desc')
            ->get();
        $count_ds_cv_noi_nn = $ds_cv_noi_nn->sum('so_luong');

        /* Tổng hợp người nhập - người xử lý */
        $tonghop_nguoinhap_nguoichuyen = DB::connection('HISPro')
        ->table('his_treatment')
        ->selectRaw('his_treatment.creator, his_treatment.doctor_username, his_treatment.doctor_loginname,
            his_treatment.is_transfer_in, count(*) as so_luong'
        )
        ->where('his_treatment.create_time', '>=', $from_date)
        ->where('his_treatment.create_time', '<=', $to_date)
        ->whereNotIn('fee_lock_loginname', $doctor)
        ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
		->whereNotIn('tdl_hein_card_number', $hein_cards)
        ->groupBy('his_treatment.creator', 'his_treatment.doctor_username', 'his_treatment.doctor_loginname', 
            'his_treatment.is_transfer_in'
        )
        ->orderBy('so_luong','DESC')
        ->orderBy('creator')
        ->orderBy('doctor_username')
        ->get();

        $danhsach_chuyenvien_traituyen = DB::connection('HISPro')
        ->table('his_treatment')
        ->selectRaw('his_treatment.treatment_code, his_treatment.creator, his_treatment.doctor_username,
            his_treatment.doctor_loginname, his_treatment.medi_org_name')
        ->where('his_treatment.create_time', '>=', $from_date)
        ->where('his_treatment.create_time', '<=', $to_date)
        ->whereNotIn('fee_lock_loginname', $doctor)
        ->whereIn('treatment_end_type_id', explode(',',config('__tech.treatment_end_type_cv_ad')))
        ->whereNull('is_transfer_in')
        ->whereNotIn('tdl_hein_medi_org_code', explode(',', getParam('cskcb_dung_tuyen')->param_value))
		->whereNotIn('tdl_hein_card_number', $hein_cards)
        ->get();
 
        $emails = email_receive_report::where('active', 1)
            ->where('bcaoadmin', 1)
            ->get();

        foreach ($emails as $key => $value) {
            Mail::send('templates.mail-bcaoadmin', array('from_date_str' => $from_date_str,
                'to_date_str' => $to_date_str,
                'name' => $value->name,
                'danhsach_chuyenvien_traituyen' => $danhsach_chuyenvien_traituyen,
                'tonghop_nguoinhap_nguoichuyen' => $tonghop_nguoinhap_nguoichuyen,
                'ds_cv_all' => $ds_cv_all,
                'count_ds_cv_all' => $count_ds_cv_all,
                'ds_cv_all_bs' => $ds_cv_all_bs,
                'count_ds_cv_all_bs' => $count_ds_cv_all_bs,
                'ds_cv_all_nn' => $ds_cv_all_nn,
                'count_ds_cv_all_nn' => $count_ds_cv_all_nn,
                'ds_cv_noi' => $ds_cv_noi,
                'count_ds_cv_noi' => $count_ds_cv_noi,
                'ds_cv_noi_bs' => $ds_cv_noi_bs,
                'count_ds_cv_noi_bs' => $count_ds_cv_noi_bs,
                'ds_cv_noi_nn' => $ds_cv_noi_nn,
                'count_ds_cv_noi_nn' => $count_ds_cv_noi_nn),
            function ($message) use ($value, $from_date_str, $to_date_str) {        
                $message->to($value->email);
                $message->subject('Báo cáo quan trọng - Từ: ' . $from_date_str . ' đến ' . $to_date_str  . '. Ngày gửi: ' . date('d/m/Y H:i'));
            });

        }

        $this->info($this->description);
    }
}
