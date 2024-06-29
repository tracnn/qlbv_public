<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();; // Mã bệnh nhân
            $table->string('name'); // Tên bệnh nhân
            $table->date('date_of_birth'); // Ngày sinh
            $table->char('gender', 1); // Giới tính, dùng char vì chỉ lưu M hoặc F
            $table->string('contact_info'); // Thông tin liên lạc
            $table->string('address'); // Địa chỉ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
