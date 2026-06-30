<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuyển watermark sang quét theo ID (PK có index) thay vì CREATE_TIME (không index trên
 * HIS_SERE_SERV/HIS_EXP_MEST_MEDICINE/HIS_MEDICINE_INTERACTIVE -> full table scan, rất chậm).
 *
 * Đặt last_id = MAX(id) hiện tại của từng bảng nguồn HIS để bắt đầu quét từ thời điểm deploy
 * (không backfill lịch sử). Chạy 1 lần.
 */
class ResetOrderCheckWatermarksToMaxId extends Migration
{
    public function up()
    {
        $hisConn = config('order_check.his_connection');
        $nowDt = date('Y-m-d H:i:s');

        // source_key (watermark) => bảng HIS tương ứng
        $map = [
            'his_service_req' => 'his_service_req',
            'his_medicine_interactive' => 'his_medicine_interactive',
            'his_sere_serv_restriction' => 'his_sere_serv',
            'his_exp_mest_medicine' => 'his_exp_mest_medicine',
        ];

        $maxCache = [];
        foreach ($map as $sourceKey => $hisTable) {
            if (!array_key_exists($hisTable, $maxCache)) {
                $maxCache[$hisTable] = (int) DB::connection($hisConn)->table($hisTable)->max('id');
            }
            $maxId = $maxCache[$hisTable];

            DB::table('order_check_watermarks')->updateOrInsert(
                ['source_key' => $sourceKey],
                ['last_id' => $maxId, 'last_run_at' => $nowDt, 'updated_at' => $nowDt]
            );
        }
    }

    public function down()
    {
        // Không khôi phục giá trị watermark cũ.
    }
}
