<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InitRestrictionWatermarkNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');
        $nowDt = date('Y-m-d H:i:s');
        DB::table('order_check_watermarks')->updateOrInsert(
            ['source_key' => 'his_sere_serv_restriction'],
            ['last_create_time' => $nowNum, 'last_modify_time' => $nowNum, 'last_id' => 0, 'last_run_at' => $nowDt, 'created_at' => $nowDt, 'updated_at' => $nowDt]
        );
    }

    public function down()
    {
        DB::table('order_check_watermarks')->where('source_key', 'his_sere_serv_restriction')->delete();
    }
}
