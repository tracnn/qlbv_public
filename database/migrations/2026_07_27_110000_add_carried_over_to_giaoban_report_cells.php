<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCarriedOverToGiaobanReportCells extends Migration
{
    public function up()
    {
        Schema::table('giaoban_report_cells', function (Blueprint $table) {
            // true = so ke thua tu phien truoc, khoa chua xac nhan
            $table->boolean('carried_over')->default(false);
        });
    }

    public function down()
    {
        Schema::table('giaoban_report_cells', function (Blueprint $table) {
            $table->dropColumn('carried_over');
        });
    }
}
