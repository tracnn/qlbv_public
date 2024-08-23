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
    protected $signature = 'kiemtrathebhyt:day';

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
            ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
            ->selectRaw('his_treatment.treatment_code, his_treatment.tdl_hein_card_number, his_treatment.tdl_patient_name,
                his_treatment.tdl_patient_gender_id, his_treatment.tdl_hein_medi_org_code,
                his_treatment.tdl_hein_card_from_time, his_treatment.tdl_hein_card_to_time,
                his_room.department_id,
                his_treatment.tdl_patient_dob, his_department.department_name')
            ->whereNull('his_treatment_bed_room.remove_time')
            ->whereNull('his_co_treatment.id')
            ->where('his_bed_room.is_active',1)
            ->where('his_room.is_active',1)
            ->whereNotNull('his_treatment.tdl_hein_card_number')
            ->whereNotIn('his_treatment.tdl_treatment_type_id', explode(',',config('__tech.treatment_type_kham')))
            ->where('his_treatment_bed_room.is_delete',0)
            ->get();

        foreach ($count_noitru_bed_room as $key => $value) {
            $maThe  = $value->tdl_hein_card_number;
            $hoTen = $value->tdl_patient_name;
            $ngaySinhFormatted  = dob($value->tdl_patient_dob);
            $ma_lk = $value->treatment_code;
            $maDKBD = $value->tdl_hein_medi_org_code;
            $gioiTinh = $value->tdl_patient_gender_id;

            $this->info($maThe);

            $params = [
                'maThe' => $maThe,
                'hoTen' => $hoTen,
                'ngaySinh' => $ngaySinhFormatted,
                'ma_lk' => $ma_lk,
                'maCSKCB' => $maDKBD,
                'gioiTinh' => $gioiTinh,
                // Thêm các thông tin khác nếu cần
            ];

            // Dispatch job
            JobKtTheBHYT::dispatch($params)
                ->onQueue('JobKtTheBHYT');
        }

        $this->info($this->description);
    }
}
