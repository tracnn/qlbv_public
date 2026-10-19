<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Anh chup tung dong cua mot ban 01/BH.
 *
 * Luu GIA TRI DA CHUAN HOA (dung cai se in ra XML) nen moi cot deu la string, ke ca tien:
 * ban la anh chup dong bang, sua XML1 ve sau khong duoc lam doi noi dung da ky.
 */
class CreateBh01DongTable extends Migration
{
    public function up()
    {
        Schema::create('bh01_dong', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ban_id');
            $table->integer('stt');
            $table->string('ma_lk', 100)->nullable();

            $table->string('ho_ten')->nullable();
            $table->string('ngay_sinh')->nullable();
            $table->string('gioi_tinh')->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('ma_benh_chinh')->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_vao_noi_tru')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->string('so_ngay_dtri')->nullable();
            $table->string('ma_loai_kcb')->nullable();
            $table->string('t_tongchi_bv')->nullable();
            $table->string('t_tongchi_bh')->nullable();
            $table->string('t_bhtt')->nullable();
            $table->string('t_bncct')->nullable();
            $table->string('t_bntt')->nullable();
            $table->string('t_nguonkhac')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->string('nam_qt')->nullable();
            $table->string('thang_qt')->nullable();

            $table->text('canh_bao')->nullable();

            $table->unique(['ban_id', 'stt'], 'uq_bh01_dong_ban_stt');
            $table->index('ma_lk', 'idx_bh01_dong_ma_lk');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bh01_dong');
    }
}
