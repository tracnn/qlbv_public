<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToBhxhEmrPermissionsTable1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bhxh_emr_permissions', function (Blueprint $table) {
            $table->string('patient_id')->nullable()->after('treatment_code')->index();
            $table->string('patient_code')->nullable()->after('patient_id');
            $table->string('patient_name')->nullable()->after('treatment_code');
            $table->string('patient_dob')->nullable()->after('patient_name');
            $table->string('patient_address')->nullable()->after('patient_dob');
            $table->string('treatment_type_id')->nullable()->after('patient_address')->index();
            $table->string('treatment_type_name')->nullable()->after('treatment_type_id');
            $table->string('patient_type_id')->nullable()->after('treatment_type_name')->index();
            $table->string('patient_type_name')->nullable()->after('patient_type_id');
            $table->string('hein_card_number')->nullable()->after('patient_type_name');
            $table->string('last_department_id')->nullable()->after('hein_card_number');
            $table->string('last_department_name')->nullable()->after('last_department_id');
            $table->string('in_time')->nullable()->after('last_department_name');
            $table->string('out_time')->nullable()->after('in_time');
            $table->string('fee_lock_time')->nullable()->after('out_time');
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
            $table->dropColumn('patient_id');
            $table->dropColumn('patient_code');
            $table->dropColumn('patient_name');
            $table->dropColumn('patient_dob');
            $table->dropColumn('patient_address');
            $table->dropColumn('treatment_type_id');
            $table->dropColumn('treatment_type_name');
            $table->dropColumn('patient_type_id');
            $table->dropColumn('patient_type_name');
            $table->dropColumn('hein_card_number');
            $table->dropColumn('last_department_id');
            $table->dropColumn('last_department_name');
            $table->dropColumn('in_time');
            $table->dropColumn('out_time');
            $table->dropColumn('fee_lock_time');
        });
    }
}
