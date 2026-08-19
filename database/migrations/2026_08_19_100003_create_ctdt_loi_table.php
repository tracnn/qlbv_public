<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtdtLoiTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_loi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();
            $table->unsignedInteger('chung_tu_id')->nullable()->index();

            $table->string('ma_loi', 20)->index();
            $table->string('ten_truong', 50)->nullable();
            $table->string('mo_ta', 255);
            $table->string('muc_do', 10)->index();   // chan | canh_bao

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_loi');
    }
}
