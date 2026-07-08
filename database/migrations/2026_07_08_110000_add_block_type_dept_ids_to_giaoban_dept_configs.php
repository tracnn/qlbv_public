<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBlockTypeDeptIdsToGiaobanDeptConfigs extends Migration
{
    public function up()
    {
        Schema::table('giaoban_dept_configs', function (Blueprint $table) {
            $table->string('block_type', 20)->default('dieu_tri')->after('display_name'); // dieu_tri|kham|can_lam_sang
            $table->text('his_department_ids')->nullable()->after('his_department_id');    // JSON mang int
        });

        foreach (DB::table('giaoban_dept_configs')->whereNotNull('his_department_id')->get() as $r) {
            DB::table('giaoban_dept_configs')->where('id', $r->id)
                ->update(['his_department_ids' => json_encode([(int) $r->his_department_id])]);
        }
    }

    public function down()
    {
        Schema::table('giaoban_dept_configs', function (Blueprint $table) {
            $table->dropColumn(['block_type', 'his_department_ids']);
        });
    }
}
