<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class sarcov2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sarcov2:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $his_sarcov2 = DB::connection('HISPro')
        ->table('his_treatment_bed_room')
        ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
        ->join('his_room','his_bed_room.room_id','=','his_room.id')
        ->join('his_department','his_room.department_id','=','his_department.id')
        ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
        ->join('his_patient','his_treatment.patient_id','=','his_patient.id')
        ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
        ->selectRaw('count(*) as so_luong,his_department.department_name')
        ->whereNull('his_treatment_bed_room.remove_time')
        ->whereNull('his_co_treatment.id')
        ->where('his_bed_room.is_active', 1)
        ->where('his_room.is_active', 1)
        ->whereIn('his_treatment.tdl_treatment_type_id', [2,3])
        ->where('his_treatment_bed_room.is_delete', 0)
        ->where('his_patient.patient_classify_id', 2)
        ->groupBy('his_department.department_name')
        ->orderBy('so_luong','desc')
        ->get();

        /* Lấy tồn đầu kỳ */
        $sarcov2_last = DB::table('sarcov2_ctu')
        ->orderBy('ngay_ctu', 'desc')
        ->first();
        /* End */

        if (count($his_sarcov2)) {
            $rtn = DB::table('sarcov2_ctu')->insert([
                'ngay_ctu' => now(),
                'so_dky' => $sarcov2_last ? $sarcov2_last->so_cky : null,
                'so_cky' => $his_sarcov2->sum('so_luong')
            ]);

            if ($rtn) {
                $sarcov2_last = DB::table('sarcov2_ctu')
                ->orderBy('ngay_ctu', 'desc')
                ->first();

                if ($sarcov2_last) {
                    foreach ($his_sarcov2 as $key => $value) {
                        DB::table('sarcov2_ct')->insert([
                            'sarcov2_ctu_id' => $sarcov2_last->id,
                            'ten_khoa' => $value->department_name,
                            'so_luong' => $value->so_luong
                        ]);
                    }
                }
            }
        }
        
    }
}
