<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsuranceCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('maKetQua');
            $table->text('ghiChu');
            $table->string('maThe')->nullable();
            $table->string('hoTen')->nullable();
            $table->string('ngaySinh')->nullable();
            $table->string('gioiTinh')->nullable();
            $table->string('diaChi')->nullable();
            $table->string('maDKBD')->nullable();
            $table->string('cqBHXH')->nullable();
            $table->string('gtTheTu')->nullable();
            $table->string('gtTheDen')->nullable();
            $table->string('maKV')->nullable();
            $table->string('ngayDu5Nam')->nullable();
            $table->string('maSoBHXH')->nullable();
            $table->string('maTheCu')->nullable();
            $table->string('maTheMoi')->nullable();
            $table->string('gtTheTuMoi')->nullable();
            $table->string('gtTheDenMoi')->nullable();
            $table->string('maDKBDMoi')->nullable();
            $table->string('tenDKBDMoi')->nullable();
            $table->longText('dsLichSuKCB2018')->nullable();
            $table->longText('dsLichSuKT2018')->nullable();
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
        Schema::dropIfExists('check_insurances');
    }
}
