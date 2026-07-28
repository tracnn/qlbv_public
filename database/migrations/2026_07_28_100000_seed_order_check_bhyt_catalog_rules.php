<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Bon quy tac doi chieu danh muc BHXH cho order-check.
 *
 * SEED O TRANG THAI TAT (is_active = false) - co chu dich.
 *
 * Ly do: khong do duoc ti le khop that truoc khi trien khai. Ba bang danh muc tren moi
 * truong phat trien deu 0 dong, va HIS co 21.778 dich vu dang hoat dong ma chi 10.552
 * khai ma BHXH (48%). Bat san co the do ra hang nghin vi pham ngay dau - cach chac chan
 * de nguoi dung tat luon tinh nang.
 *
 * Quy trinh: chay `php artisan kiemtraylenh:thu --ngay=7` de dem truoc ma khong ghi gi,
 * xem con so, roi bat tung quy tac tren man Quan ly quy tac.
 */
class SeedOrderCheckBhytCatalogRules extends Migration
{
    public function up()
    {
        $now = now();

        $rules = [
            [
                'code' => 'A_BHYT_CODE_MISSING',
                'rule_type' => 'BhytCodeMissingRule',
                'name' => 'DVKT/Thuốc/VTYT cho đối tượng BHYT chưa khai mã BHYT',
            ],
            [
                'code' => 'A_BHYT_SERVICE_NOT_IN_CATALOG',
                'rule_type' => 'BhytServiceCatalogRule',
                'name' => 'Mã dịch vụ không có trong danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_DRUG_NOT_IN_CATALOG',
                'rule_type' => 'BhytDrugCatalogRule',
                'name' => 'Mã thuốc không có trong danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_SUPPLY_NOT_IN_CATALOG',
                'rule_type' => 'BhytSupplyCatalogRule',
                'name' => 'Mã vật tư không có trong danh mục BHYT',
            ],
        ];

        foreach ($rules as $r) {
            if (DB::table('order_check_rules')->where('code', $r['code'])->exists()) {
                continue;
            }

            DB::table('order_check_rules')->insert([
                'code' => $r['code'],
                'family' => 'A',
                'rule_type' => $r['rule_type'],
                'name' => $r['name'],
                'severity' => 'warning',
                'params' => null,
                'scope' => null,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', [
            'A_BHYT_CODE_MISSING',
            'A_BHYT_SERVICE_NOT_IN_CATALOG',
            'A_BHYT_DRUG_NOT_IN_CATALOG',
            'A_BHYT_SUPPLY_NOT_IN_CATALOG',
        ])->delete();
    }
}
