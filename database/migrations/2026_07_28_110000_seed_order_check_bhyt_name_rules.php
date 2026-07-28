<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Ba quy tac doi chieu TEN theo danh muc BHYT cho order-check.
 *
 * SEED O TRANG THAI TAT (is_active = false) - co chu dich, cung ly do voi dot quy tac ma:
 * ba bang danh muc tren moi truong phat trien deu 0 dong nen khong do duoc ti le lech ten
 * that truoc khi trien khai. Phep so la TUYET DOI (chi trim, phan biet hoa thuong), nen so
 * vi pham ban dau co the rat cao.
 *
 * Quy trinh: nap du ba bang danh muc, chay `php artisan kiemtraylenh:thu --ngay=7` de dem
 * truoc ma khong ghi gi, xem con so, roi bat tung quy tac tren man Quan ly quy tac.
 */
class SeedOrderCheckBhytNameRules extends Migration
{
    public function up()
    {
        $now = now();

        $rules = [
            [
                'code' => 'A_BHYT_SERVICE_NAME_MISMATCH',
                'rule_type' => 'BhytServiceNameRule',
                'name' => 'Tên dịch vụ lệch danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_DRUG_NAME_MISMATCH',
                'rule_type' => 'BhytDrugNameRule',
                'name' => 'Tên thuốc lệch danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_SUPPLY_NAME_MISMATCH',
                'rule_type' => 'BhytSupplyNameRule',
                'name' => 'Tên vật tư lệch danh mục BHYT',
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
            'A_BHYT_SERVICE_NAME_MISMATCH',
            'A_BHYT_DRUG_NAME_MISMATCH',
            'A_BHYT_SUPPLY_NAME_MISMATCH',
        ])->delete();
    }
}
