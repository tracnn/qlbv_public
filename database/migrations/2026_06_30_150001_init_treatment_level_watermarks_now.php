<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InitTreatmentLevelWatermarksNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');
        $nowDt = date('Y-m-d H:i:s');
        foreach (['his_sere_serv', 'his_exp_mest_medicine'] as $key) {
            DB::table('order_check_watermarks')->updateOrInsert(
                ['source_key' => $key],
                [
                    'last_create_time' => $nowNum, 'last_modify_time' => $nowNum, 'last_id' => 0,
                    'last_run_at' => $nowDt, 'created_at' => $nowDt, 'updated_at' => $nowDt,
                ]
            );
        }
    }

    public function down()
    {
        DB::table('order_check_watermarks')->whereIn('source_key', ['his_sere_serv', 'his_exp_mest_medicine'])->delete();
    }
}
