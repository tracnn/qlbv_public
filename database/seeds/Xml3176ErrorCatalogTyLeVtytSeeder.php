<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn error_code đối chiếu tỷ lệ thanh toán BHYT của VTYT (XML3) với danh mục.
 * Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogTyLeVtytSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XML3', 'XML3_INVALID_APPROVED_TYLE_TT_BH', 'Tỷ lệ thanh toán BHYT của VTYT không khớp tỷ lệ duyệt'],
        ];

        foreach ($rules as $r) {
            list($xml, $code, $name) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $code],
                ['error_name' => $name, 'description' => $name, 'critical_error' => true, 'is_check' => true]
            );
        }
    }
}
