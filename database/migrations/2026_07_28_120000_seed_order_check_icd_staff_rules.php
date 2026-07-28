<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Ba quy tac doi chieu danh muc ICD, ICD YHCT va nhan vien y te cho order-check.
 *
 * SEED O TRANG THAI TAT (is_active = false), moi quy tac mot ly do khac nhau:
 *
 *   A_ICD_NOT_IN_CATALOG        Quy mo lon: 11.887 dong vi pham moi 7 ngay do 287 ma gay
 *                               ra (9.962/60.682 phieu, 16,42%). Danh muc da co 12.229
 *                               dong nen bat la chay that ngay - can xac nhan con so tren
 *                               may chu that truoc.
 *   A_ICD_YHCT_NOT_IN_CATALOG   Do duoc 0 vi pham tren 1.199 phieu co ma YHCT. Bat cung
 *                               khong doi gi. Luat nay se IM LANG - do la DUNG, khong
 *                               phai hong.
 *   A_STAFF_CERT_NOT_IN_CATALOG medical_staffs dang 0 dong.
 *
 * Quy trinh: chay `php artisan kiemtraylenh:thu --ngay=7` de dem truoc ma khong ghi gi,
 * xem con so, roi bat tung quy tac tren man Quan ly quy tac.
 */
class SeedOrderCheckIcdStaffRules extends Migration
{
    public function up()
    {
        $now = now();

        $rules = [
            [
                'code' => 'A_ICD_NOT_IN_CATALOG',
                'rule_type' => 'IcdNotInCatalogRule',
                'name' => 'Mã bệnh không có trong danh mục ICD10',
            ],
            [
                'code' => 'A_ICD_YHCT_NOT_IN_CATALOG',
                'rule_type' => 'IcdYhctNotInCatalogRule',
                'name' => 'Mã bệnh YHCT không có trong danh mục ICD YHCT',
            ],
            [
                'code' => 'A_STAFF_CERT_NOT_IN_CATALOG',
                'rule_type' => 'StaffCertNotInCatalogRule',
                'name' => 'CCHN không có trong danh mục nhân viên y tế',
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
            'A_ICD_NOT_IN_CATALOG',
            'A_ICD_YHCT_NOT_IN_CATALOG',
            'A_STAFF_CERT_NOT_IN_CATALOG',
        ])->delete();
    }
}
