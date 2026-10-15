<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Nap ma XML3_EXECUTION_TIME_TOO_LONG (PTTT keo dai qua nguong, 15/10/2026) o muc NGHIEM TRONG:
 * ho so truoc day bi chan do "chong gio" gia (dong Hut dom ghi NGAY_KQ cuoi dot) van bi chan.
 * firstOrCreate: chay lai khong nhan doi, khong ghi de muc nguoi van hanh da chinh.
 */
class NapMaLoiThoiGianPtttDai extends Migration
{
    public function up()
    {
        Xml3176ErrorCatalog::firstOrCreate(
            ['xml' => 'XML3', 'error_code' => 'XML3_EXECUTION_TIME_TOO_LONG'],
            [
                'error_name'     => 'Thời gian thực hiện dịch vụ dài bất thường',
                'description'    => 'Dịch vụ PTTT có khoảng NGAY_TH_YL – NGAY_KQ dài hơn ngưỡng cấu hình (mặc định 24 giờ, riêng CVVHDF 72 giờ, VAC 48 giờ)',
                'critical_error' => true,
                'is_check'       => true,
            ]
        );
    }

    public function down()
    {
        // Giu dong danh muc de khong mat lich su loi da ghi.
    }
}
