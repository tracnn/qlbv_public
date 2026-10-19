<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Moi lan bam gui mot dong: ban chi giu ket qua gan nhat, con day la dau vet doi soat.
 */
class CreateBh01LichSuGuiTable extends Migration
{
    public function up()
    {
        Schema::create('bh01_lich_su_gui', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ban_id')->index();
            $table->timestamp('gui_luc')->nullable();
            $table->string('gui_boi')->nullable();
            $table->string('ma_ket_qua', 10)->nullable();
            $table->string('ma_gd', 100)->nullable();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();
            $table->text('thong_diep')->nullable();
            $table->text('loi')->nullable();
            // Phan hoi goc, da cat ngan hop ly o noi ghi.
            $table->text('nguyen_van')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bh01_lich_su_gui');
    }
}
