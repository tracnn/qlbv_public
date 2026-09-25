<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Mã lỗi CẢNH BÁO "Trường hợp đặc biệt được cộng 1 ngày giường nhưng chưa khai".
 *
 * Nạp bằng migration (không phải seeder) vì mã lỗi chưa có trong danh mục bị MẶC ĐỊNH coi là
 * NGHIÊM TRỌNG (Xml3176ErrorService::getCriticalErrorStatus) - quên chạy seeder là chặn hàng
 * trăm hồ sơ. Chỉ THÊM khi chưa có: không ghi đè mức lỗi người dùng đã chỉnh.
 */
class ThemMaLoiNgayGiuongDacBietChuaKhai extends Migration
{
    const MA = 'XMLComplete_BED_DAYS_SPECIAL_NOT_CLAIMED';

    public function up()
    {
        if (DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->exists()) {
            return;
        }

        DB::table('xml3176_error_catalogs')->insert([
            'xml'            => 'XMLComplete',
            'error_code'     => self::MA,
            'error_name'     => 'Trường hợp đặc biệt được cộng 1 ngày giường nhưng chưa khai',
            'description'    => 'Tử vong / chuyển viện / nặng xin về được cộng 1 ngày giường theo TT39 nhưng hồ sơ chưa khai ngày cộng thêm',
            'critical_error' => false,
            'is_check'       => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down()
    {
        DB::table('xml3176_error_catalogs')->where('error_code', self::MA)->delete();
    }
}
