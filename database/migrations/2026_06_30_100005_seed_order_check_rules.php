<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed toàn bộ luật đang dùng (idempotent theo code).
 * Họ B = hợp lệ cấu trúc/thời gian & hành nghề (hardcode).
 * Họ A = lâm sàng (data-driven / scanner).
 */
class SeedOrderCheckRules extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            // ===== Họ B =====
            ['code' => 'B_DISCHARGE_BEFORE_ADMISSION', 'family' => 'B', 'rule_type' => 'DischargeBeforeAdmissionRule', 'name' => 'Ngày ra viện trước ngày vào viện', 'severity' => 'critical'],
            ['code' => 'B_ORDER_TIME_OUT_OF_STAY',     'family' => 'B', 'rule_type' => 'OrderTimeOutOfStayRule',     'name' => 'Giờ y lệnh ngoài khoảng đợt điều trị', 'severity' => 'warning'],
            ['code' => 'B_EXECUTE_BEFORE_ORDER',       'family' => 'B', 'rule_type' => 'ExecuteBeforeOrderRule',     'name' => 'Giờ thực hiện trước giờ y lệnh', 'severity' => 'warning'],
            ['code' => 'B_DOCTOR_NO_PRACTICE_CERT',    'family' => 'B', 'rule_type' => 'DoctorPracticeCertRule',     'name' => 'Người thực hiện thiếu chứng chỉ hành nghề', 'severity' => 'critical'],
            // ===== Họ A =====
            ['code' => 'A_DRUG_INTERACTION',           'family' => 'A', 'rule_type' => 'InteractionLogScanner',     'name' => 'Tương tác thuốc (HIS phát hiện)', 'severity' => 'warning'],
            ['code' => 'A_MISSING_DIAGNOSIS',          'family' => 'A', 'rule_type' => 'MissingDiagnosisRule',      'name' => 'Phiếu chỉ định thiếu chẩn đoán ICD', 'severity' => 'warning'],
            ['code' => 'A_DOSE_MISMATCH',              'family' => 'A', 'rule_type' => 'DoseSanityRule',            'name' => 'Liều × ngày không khớp số lượng cấp', 'severity' => 'info'],
            ['code' => 'A_GENDER_MISMATCH',            'family' => 'A', 'rule_type' => 'GenderRestrictionRule',     'name' => 'Chỉ định DV sai giới tính (theo danh mục)', 'severity' => 'warning'],
            ['code' => 'A_AGE_OUT_OF_RANGE',           'family' => 'A', 'rule_type' => 'AgeRestrictionRule',        'name' => 'Chỉ định DV ngoài ngưỡng tuổi (theo danh mục)', 'severity' => 'warning'],
        ];

        foreach ($rules as $r) {
            if (!DB::table('order_check_rules')->where('code', $r['code'])->exists()) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'],
                    'family' => $r['family'],
                    'rule_type' => $r['rule_type'],
                    'name' => $r['name'],
                    'severity' => $r['severity'],
                    'params' => null,
                    'scope' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', [
            'B_DISCHARGE_BEFORE_ADMISSION', 'B_ORDER_TIME_OUT_OF_STAY', 'B_EXECUTE_BEFORE_ORDER', 'B_DOCTOR_NO_PRACTICE_CERT',
            'A_DRUG_INTERACTION', 'A_MISSING_DIAGNOSIS', 'A_DOSE_MISMATCH', 'A_GENDER_MISMATCH', 'A_AGE_OUT_OF_RANGE',
        ])->delete();
    }
}
