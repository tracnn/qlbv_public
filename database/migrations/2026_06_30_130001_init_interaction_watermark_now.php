<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Khởi tạo mốc quét nguồn his_medicine_interactive = thời điểm triển khai.
 * Chỉ bắt tương tác thuốc PHÁT SINH MỚI từ lúc deploy, không backfill lịch sử.
 */
class InitInteractionWatermarkNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');
        $nowDt = date('Y-m-d H:i:s');
        DB::table('order_check_watermarks')->updateOrInsert(
            ['source_key' => 'his_medicine_interactive'],
            [
                'last_create_time' => $nowNum,
                'last_modify_time' => $nowNum,
                'last_id' => 0,
                'last_run_at' => $nowDt,
                'created_at' => $nowDt,
                'updated_at' => $nowDt,
            ]
        );
    }

    public function down()
    {
        DB::table('order_check_watermarks')->where('source_key', 'his_medicine_interactive')->delete();
    }
}
