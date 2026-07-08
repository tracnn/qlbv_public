<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDeptConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_dept_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('his_department_id')->nullable(); // null = khối không gắn khoa HIS (VD: XN & CĐHA gộp)
            $table->string('display_name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('metrics'); // JSON: danh sách chỉ tiêu
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_dept_configs');
    }
}
