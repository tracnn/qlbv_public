<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRuleLogsTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_rule_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_key', 100);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('scanned_count')->default(0);
            $table->unsignedInteger('violation_count')->default(0);
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_rule_logs');
    }
}
