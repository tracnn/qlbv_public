<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToBhxhEmrPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bhxh_emr_permissions', function (Blueprint $table) {
            $table->string('treatment_end_type_id')->nullable()->after('last_department_name')->index();
            $table->string('treatment_end_type_name')->nullable()->after('treatment_end_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bhxh_emr_permissions', function (Blueprint $table) {
            $table->dropColumn('treatment_end_type_id');
            $table->dropColumn('treatment_end_type_name');
        });
    }
}
