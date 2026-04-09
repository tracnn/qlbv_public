<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoDinhDanhToMedicalStaffsTable extends Migration
{
    public function up()
    {
        Schema::table('medical_staffs', function (Blueprint $table) {
            $table->string('so_dinh_danh')->nullable()->after('gioi_tinh');
        });
    }

    public function down()
    {
        Schema::table('medical_staffs', function (Blueprint $table) {
            $table->dropColumn('so_dinh_danh');
        });
    }
}
