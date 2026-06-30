<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisplayColumnsToOrderCheckViolations extends Migration
{
    public function up()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->string('service_req_code', 50)->nullable()->after('treatment_code');
            $table->unsignedInteger('service_req_type_id')->nullable()->after('service_req_code');
            $table->string('service_req_type_name', 255)->nullable()->after('service_req_type_id');
            $table->string('service_code', 50)->nullable()->after('service_req_type_name');
            $table->string('service_name', 255)->nullable()->after('service_code');
            $table->string('department_code', 50)->nullable()->after('department_id');
            $table->string('department_name', 255)->nullable()->after('department_code');

            $table->index('service_req_type_id');
        });
    }

    public function down()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->dropIndex(['service_req_type_id']);
            $table->dropColumn([
                'service_req_code', 'service_req_type_id', 'service_req_type_name',
                'service_code', 'service_name', 'department_code', 'department_name',
            ]);
        });
    }
}
