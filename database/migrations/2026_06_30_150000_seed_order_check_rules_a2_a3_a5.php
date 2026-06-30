<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckRulesA2A3A5 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        // A2 (trùng hoạt chất) & A3 (trùng dịch vụ) đã loại khỏi đợt này, sẽ nghiên cứu sau.
        $rules = [
            ['code' => 'A_DOSE_MISMATCH', 'rule_type' => 'DoseSanityRule', 'name' => 'Liều × ngày không khớp số lượng cấp', 'severity' => 'info'],
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
        DB::table('order_check_rules')->whereIn('code', ['A_DOSE_MISMATCH'])->delete();
    }
}
