<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckStructuralRules extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'B_DISCHARGE_BEFORE_ADMISSION', 'rule_type' => 'DischargeBeforeAdmissionRule', 'name' => 'Ngày ra viện trước ngày vào viện', 'severity' => 'critical'],
            ['code' => 'B_ORDER_TIME_OUT_OF_STAY',     'rule_type' => 'OrderTimeOutOfStayRule',     'name' => 'Giờ y lệnh ngoài khoảng đợt điều trị', 'severity' => 'warning'],
            ['code' => 'B_EXECUTE_BEFORE_ORDER',       'rule_type' => 'ExecuteBeforeOrderRule',     'name' => 'Giờ thực hiện trước giờ y lệnh', 'severity' => 'warning'],
            ['code' => 'B_DOCTOR_NO_PRACTICE_CERT',    'rule_type' => 'DoctorPracticeCertRule',     'name' => 'Bác sĩ thiếu chứng chỉ hành nghề', 'severity' => 'critical'],
        ];

        foreach ($rules as $r) {
            $exists = DB::table('order_check_rules')->where('code', $r['code'])->exists();
            if (!$exists) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'],
                    'family' => 'B',
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
            'B_DISCHARGE_BEFORE_ADMISSION',
            'B_ORDER_TIME_OUT_OF_STAY',
            'B_EXECUTE_BEFORE_ORDER',
            'B_DOCTOR_NO_PRACTICE_CERT',
        ])->delete();
    }
}
