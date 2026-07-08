<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->date('report_date')->unique();
            $table->dateTime('from_time');
            $table->dateTime('to_time');
            $table->string('status', 20)->default('draft'); // draft|final
            $table->text('general_note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedInteger('unlocked_by')->nullable();
            $table->dateTime('unlocked_at')->nullable();
            $table->dateTime('data_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_reports');
    }
}
