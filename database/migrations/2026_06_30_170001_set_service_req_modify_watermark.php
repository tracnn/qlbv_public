<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ServiceReqScanner chuyển sang quét theo MODIFY_TIME (HIS_SERVICE_REQ.modify_time có index)
 * để bắt cả phiếu bị sửa sau khi tạo (vd người thực hiện được gán lúc thực hiện).
 *
 * Đặt last_modify_time = MAX(modify_time) hiện tại để bắt đầu từ thời điểm deploy
 * (không quét lại toàn bộ phiếu đã sửa trong lịch sử). Chạy 1 lần.
 */
class SetServiceReqModifyWatermark extends Migration
{
    public function up()
    {
        $hisConn = config('order_check.his_connection');
        $maxModify = (int) DB::connection($hisConn)->table('his_service_req')->max('modify_time');

        DB::table('order_check_watermarks')->updateOrInsert(
            ['source_key' => 'his_service_req'],
            ['last_modify_time' => $maxModify, 'last_run_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public function down()
    {
        // Không khôi phục giá trị watermark cũ.
    }
}
