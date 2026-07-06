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
        $conn = config('order_check.his_connection');
        $now = date('Y-m-d H:i:s');

        $maxModifyServiceReq = (int) DB::connection($conn)->table('his_service_req')->max('modify_time');
        $maxIdInteractive    = (int) DB::connection($conn)->table('his_medicine_interactive')->max('id');
        $maxIdExpMest        = (int) DB::connection($conn)->table('his_exp_mest_medicine')->max('id');
        $maxIdSereServ       = (int) DB::connection($conn)->table('his_sere_serv')->max('id');

        $rows = [
            ['source_key' => 'his_service_req',           'last_modify_time' => $maxModifyServiceReq, 'last_id' => 0],
            ['source_key' => 'his_medicine_interactive',  'last_modify_time' => 0, 'last_id' => $maxIdInteractive],
            ['source_key' => 'his_exp_mest_medicine',     'last_modify_time' => 0, 'last_id' => $maxIdExpMest],
            ['source_key' => 'his_sere_serv_restriction', 'last_modify_time' => 0, 'last_id' => $maxIdSereServ],
        ];

        foreach ($rows as $r) {
            DB::table('order_check_watermarks')->updateOrInsert(
                ['source_key' => $r['source_key']],
                [
                    'last_create_time' => 0,
                    'last_modify_time' => $r['last_modify_time'],
                    'last_id' => $r['last_id'],
                    'last_run_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down()
    {
        DB::table('order_check_watermarks')->whereIn('source_key', [
            'his_service_req', 'his_medicine_interactive', 'his_exp_mest_medicine', 'his_sere_serv_restriction',
        ])->delete();
    }
}
