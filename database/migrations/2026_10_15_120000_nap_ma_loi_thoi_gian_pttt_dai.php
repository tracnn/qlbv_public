<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Nap ma XML3_EXECUTION_TIME_TOO_LONG (PTTT keo dai qua nguong, 15/10/2026) o muc CANH BAO, nhu
 * XML3_OVERLAPPING_SERVICE_EXECUTION tren prod (critical_error = 0): khong chan xuat ~516 ho so.
 *
 * updateOrCreate (ep muc) chu khong firstOrCreate: neu code moi chay truoc migrate,
 * getCriticalErrorStatus mac dinh true va createOrUpdate da tu tao dong o muc NGHIEM TRONG.
 * Ma moi chua co ai chinh tay nen ep khong de mat gi.
 */
class NapMaLoiThoiGianPtttDai extends Migration
{
    public function up()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XML3', 'error_code' => 'XML3_EXECUTION_TIME_TOO_LONG'],
            [
                'error_name'     => 'Thời gian thực hiện dịch vụ dài bất thường',
                'description'    => 'Dịch vụ PTTT có khoảng NGAY_TH_YL – NGAY_KQ dài hơn ngưỡng cấu hình (mặc định 24 giờ, riêng CVVHDF 72 giờ, VAC 48 giờ)',
                'critical_error' => false,
                'is_check'       => true,
            ]
        );
    }

    public function down()
    {
        // Giu dong danh muc de khong mat lich su loi da ghi.
    }
}
