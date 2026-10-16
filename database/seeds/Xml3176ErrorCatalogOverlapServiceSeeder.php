<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp error_code kiểm tra hai dịch vụ PTTT chồng thời gian thực hiện trong cùng hồ sơ
 * (lỗi giám định 2454). Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogOverlapServiceSeeder extends Seeder
{
    public function run()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XML3', 'error_code' => 'XML3_OVERLAPPING_SERVICE_EXECUTION'],
            [
                'error_name'     => 'Thời gian thực hiện trùng với dịch vụ khác',
                'description'    => 'Hai dịch vụ PTTT trong cùng hồ sơ có khoảng thời gian thực hiện chồng nhau',
                'critical_error' => true,
                'is_check'       => true,
            ]
        );

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
}
