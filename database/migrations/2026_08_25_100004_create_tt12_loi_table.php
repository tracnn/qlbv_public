<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTt12LoiTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_loi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();

            // Rong khi loi thuoc ve ca ho so (vi du STT trung nhau), khong ve mot dong.
            $table->integer('stt_dong')->nullable()->index();

            $table->string('cot', 50)->nullable();
            $table->string('ma_loi', 50)->index();

            // 'loi' chan ky/gui, 'canh_bao' khong chan. so_loi cua ho so CHI dem 'loi'.
            $table->string('muc_do', 10)->default('loi')->index();

            $table->text('mo_ta');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_loi');
    }
}
