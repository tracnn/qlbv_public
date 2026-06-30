<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckViolationsTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_violations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_id')->nullable();
            $table->string('rule_code', 100);
            $table->unsignedBigInteger('treatment_id')->nullable();
            $table->string('treatment_code', 50)->nullable();
            $table->string('patient_code', 50)->nullable();
            $table->string('patient_name', 255)->nullable();
            $table->string('doctor_loginname', 100)->nullable();
            $table->string('doctor_username', 255)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('order_ref_type', 30);
            $table->unsignedBigInteger('order_ref_id');
            $table->string('severity', 20)->default('warning');
            $table->text('message');
            $table->text('detail')->nullable();
            $table->string('dedup_key', 200)->unique();
            $table->string('status', 20)->default('new');
            $table->dateTime('detected_at');
            $table->string('processed_by', 100)->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('treatment_id');
            $table->index('status');
            $table->index('detected_at');
            $table->index('rule_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_violations');
    }
}
