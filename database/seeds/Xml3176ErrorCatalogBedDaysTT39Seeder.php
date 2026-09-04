<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp error_code cảnh báo ngày giường nhỏ hơn TT39 (lỗi giám định 440).
 * critical_error = false: đây là CẢNH BÁO, không chặn xuất XML.
 * Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogBedDaysTT39Seeder extends Seeder
{
    public function run()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XMLComplete', 'error_code' => 'XMLComplete_BED_DAYS_BELOW_TT39'],
            [
                'error_name'     => 'Tổng ngày giường nhỏ hơn hướng dẫn TT39',
                'description'    => 'Tổng ngày giường khai nhỏ hơn số ngày điều trị nội trú tính theo TT39',
                'critical_error' => false,
                'is_check'       => true,
            ]
        );
    }
}
