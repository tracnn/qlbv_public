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
    }
}
