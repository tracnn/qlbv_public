<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Khởi tạo mốc quét = thời điểm triển khai (chỉ bắt y lệnh phát sinh/sửa MỚI từ lúc deploy,
 * không backfill lịch sử).
 *
 * - his_service_req: quét theo MODIFY_TIME (có index) -> mốc = MAX(modify_time).
 * - his_medicine_interactive / his_exp_mest_medicine / his_sere_serv_restriction: quét theo ID (PK)
 *   -> mốc last_id = MAX(id) của bảng nguồn tương ứng.
 */
class InitOrderCheckWatermarks extends Migration
{
    public function up()
    {
        // Cố ý KHÔNG đọc HIS (Oracle) tại đây: migrate không được phụ thuộc Oracle-CLI
        // (nhiều CSKCB CLI chưa kết nối được Oracle -> Oci8:460 khi deploy).
        // Việc đặt mốc = MAX hiện tại đã dời sang runtime: OrderCheckEngine::getWatermark()
        // khởi tạo lần đầu qua HisOrderSource::initialWatermark() (không backfill lịch sử).
    }

    public function down()
    {
        DB::table('order_check_watermarks')->whereIn('source_key', [
            'his_service_req', 'his_medicine_interactive', 'his_exp_mest_medicine', 'his_sere_serv_restriction',
        ])->delete();
    }
}
