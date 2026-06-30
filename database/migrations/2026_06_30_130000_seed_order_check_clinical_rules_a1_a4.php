<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckClinicalRulesA1A4 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'A_DRUG_INTERACTION', 'rule_type' => 'InteractionLogScanner', 'name' => 'Tương tác thuốc (HIS phát hiện)', 'severity' => 'warning'],
            ['code' => 'A_MISSING_DIAGNOSIS', 'rule_type' => 'MissingDiagnosisRule', 'name' => 'Phiếu chỉ định thiếu chẩn đoán ICD', 'severity' => 'warning'],
        ];

        foreach ($rules as $r) {
            $exists = DB::table('order_check_rules')->where('code', $r['code'])->exists();
            if (!$exists) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'],
                    'family' => 'A',
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
        DB::table('order_check_rules')->whereIn('code', ['A_DRUG_INTERACTION', 'A_MISSING_DIAGNOSIS'])->delete();
    }
}
