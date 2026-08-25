<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Moi lan bam gui them mot dong, KE CA lan hong.
 *
 * Giu dau vet doi soat sau khi cac cot trang thai tren tt12_ho_so bi ghi de o lan gui
 * sau. Khong co bang nay thi mot ho so gui ba lan chi con dau vet cua lan cuoi.
 */
class CreateTt12LichSuGuiTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_lich_su_gui', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();
            $table->string('ma_ho_so', 100)->index();

            $table->timestamp('gui_luc')->nullable();
            $table->string('gui_boi')->nullable();
            $table->string('ma_ket_qua', 10)->nullable();
            $table->string('ma_gd', 100)->nullable();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();
            $table->text('thong_diep')->nullable();
            $table->text('loi')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_lich_su_gui');
    }
}
