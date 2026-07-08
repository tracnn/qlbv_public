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
            $table->unsignedInteger('his_department_id')->nullable(); // legacy fallback (1 khoa HIS đơn)
            $table->text('his_department_ids')->nullable();            // JSON mảng int khoa HIS gộp
            $table->string('block_type', 20)->default('dieu_tri');    // dieu_tri | kham | can_lam_sang
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
