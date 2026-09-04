<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn 2 error_code kiểm tra mức hưởng (XMLComplete) để cấu hình trước lần
 * scan đầu. Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogMucHuongSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XMLComplete', 'XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT', 'Mức hưởng vượt quyền lợi thẻ (đúng tuyến, chi phí >= 15% lương cơ sở)'],
            ['XMLComplete', 'XMLComplete_MUC_HUONG_TRAI_TUYEN_TW', 'Mức hưởng vượt 40% (trái tuyến nội trú tuyến TW)'],
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
