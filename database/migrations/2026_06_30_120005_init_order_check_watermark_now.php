<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Khởi tạo mốc quét = thời điểm triển khai (thời điểm chạy migration / update phần mềm).
 * Mục đích: chỉ bắt đầu kiểm tra các y lệnh PHÁT SINH MỚI từ lúc deploy trở đi,
 * không backfill toàn bộ lịch sử HIS.
 *
 * Đồng thời dọn dữ liệu test phát sinh trong quá trình phát triển (nếu có)
 * để bắt đầu sạch. Migration chỉ chạy 1 lần.
 */
class InitOrderCheckWatermarkNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');     // YYYYMMDDHHMMSS, khớp định dạng *_TIME của HIS
        $nowDt = date('Y-m-d H:i:s');

        DB::table('order_check_watermarks')->updateOrInsert(
            ['source_key' => 'his_service_req'],
            [
                'last_create_time' => $nowNum,
                'last_modify_time' => $nowNum,
                'last_id' => 0,
                'last_run_at' => $nowDt,
                'created_at' => $nowDt,
                'updated_at' => $nowDt,
            ]
        );

        // Dọn dữ liệu test (an toàn vì migration chạy 1 lần, trước khi vận hành thật).
        DB::table('order_check_violations')->truncate();
        DB::table('order_check_rule_logs')->truncate();
    }

    public function down()
    {
        // Không khôi phục được mốc thời gian / dữ liệu test đã dọn.
        DB::table('order_check_watermarks')->where('source_key', 'his_service_req')->delete();
    }
}
