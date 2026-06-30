<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckRulesGenderAge extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'A_GENDER_MISMATCH', 'rule_type' => 'GenderRestrictionRule', 'name' => 'Chỉ định DV sai giới tính (theo danh mục)', 'severity' => 'warning'],
            ['code' => 'A_AGE_OUT_OF_RANGE', 'rule_type' => 'AgeRestrictionRule', 'name' => 'Chỉ định DV ngoài ngưỡng tuổi (theo danh mục)', 'severity' => 'warning'],
        ];
        foreach ($rules as $r) {
            if (!DB::table('order_check_rules')->where('code', $r['code'])->exists()) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'], 'family' => 'A', 'rule_type' => $r['rule_type'],
                    'name' => $r['name'], 'severity' => $r['severity'],
                    'params' => null, 'scope' => null, 'is_active' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', ['A_GENDER_MISMATCH', 'A_AGE_OUT_OF_RANGE'])->delete();
    }
}
