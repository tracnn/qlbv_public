<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use DB;
use App\Jobs\JobKtTheBHYT;

class HISProKiemTraTheBHYT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kiemtrathebhyt:day
        {--thu : Chi dem va thong ke, KHONG dispatch job - dung de nghiem thu ma khong goi len cong BHXH}';

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
            ->join('his_gender', 'his_gender.id', '=', 'his_treatment.tdl_patient_gender_id')
            ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
            ->leftJoin('his_branch','his_branch.id','=','his_treatment.branch_id')
            ->selectRaw('his_treatment.treatment_code, his_treatment.tdl_hein_card_number, his_treatment.tdl_patient_name,
                his_treatment.tdl_patient_gender_id, his_treatment.tdl_hein_medi_org_code,
                his_treatment.tdl_hein_card_from_time, his_treatment.tdl_hein_card_to_time,
                his_room.department_id, his_gender.gender_code,
                his_treatment.tdl_patient_dob, his_department.department_name
                , his_branch.hein_medi_org_code as ma_cskcb')
            ->whereNull('his_treatment_bed_room.remove_time')
            ->whereNull('his_co_treatment.id')
            ->where('his_bed_room.is_active',1)
            ->where('his_room.is_active',1)
            ->whereNotNull('his_treatment.tdl_hein_card_number')
            ->whereNotIn('his_treatment.tdl_treatment_type_id', explode(',',config('__tech.treatment_type_kham')))
            ->where('his_treatment_bed_room.is_delete',0)
            ->get();

        $dsCoSo = config('organization.BHYT_CO_SO', []);
        $thu = (bool) $this->option('thu');
        $boQua = 0;
        $demTheoCoSo = [];

        foreach ($count_noitru_bed_room as $key => $value) {
            $maCskcb = trim((string) $value->ma_cskcb);
            $ma_lk = $value->treatment_code;

            // Co so dieu tri lay tu his_branch, KHONG phai tdl_hein_medi_org_code - cot do
            // la noi DKBD cua BENH NHAN. Do 45.995 ho so: 4.194 gia tri DKBD khac nhau so
            // voi 2 co so dieu tri, trung nhau chi 0,5%.
            if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
                $boQua++;
                \Log::warning('Kiem tra the BHYT: bo qua ho so vi khong xac dinh duoc co so', [
                    'ma_lk' => $ma_lk,
                    'ma_cskcb' => $maCskcb,
                ]);
                continue;
            }

            $gioiTinh = (int) $value->gender_code;

            if ($gioiTinh === 1) {
                $gioiTinh = 2;
            } elseif ($gioiTinh === 2) {
                $gioiTinh = 1;
            }

            $params = [
                'maThe' => $value->tdl_hein_card_number,
                'hoTen' => $value->tdl_patient_name,
                'ngaySinh' => dob($value->tdl_patient_dob),
                'ma_lk' => $ma_lk,
                'maCskcb' => $maCskcb,
                'maDkbd' => $value->tdl_hein_medi_org_code,
                'gioiTinh' => $gioiTinh,
            ];

            $demTheoCoSo[$maCskcb] = isset($demTheoCoSo[$maCskcb]) ? $demTheoCoSo[$maCskcb] + 1 : 1;

            if ($thu) {
                continue;
            }

            JobKtTheBHYT::dispatch($params)->onQueue('JobKtTheBHYT');
        }

        foreach ($demTheoCoSo as $ma => $n) {
            $this->info('Co so ' . $ma . ': ' . $n . ' ho so');
        }

        $this->info('Bo qua vi khong xac dinh duoc co so: ' . $boQua);
        $this->info($this->description);
    }
}
