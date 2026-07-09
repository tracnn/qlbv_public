<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportBedsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_beds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('department_id');
            $table->integer('total_beds')->default(0);
            $table->integer('used_beds')->default(0);
            $table->timestamps();
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_beds');
    }
}
