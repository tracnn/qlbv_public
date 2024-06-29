<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDanhMucThuocTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medicine_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_thuoc');
            $table->string('ten_hoat_chat');
            $table->string('ten_thuoc');
            $table->string('don_vi_tinh');
            $table->string('ham_luong', 1024);
            $table->string('duong_dung');
            $table->string('ma_duong_dung');
            $table->string('dang_bao_che');
            $table->string('so_dang_ky');
            $table->integer('so_luong')->nullable();
            $table->decimal('don_gia', 18, 2)->nullable();
            $table->decimal('don_gia_bh', 18, 2)->nullable();
            $table->string('quy_cach')->nullable();
            $table->string('nha_sx')->nullable();
            $table->string('nuoc_sx')->nullable();
            $table->string('nha_thau')->nullable();
            $table->string('tt_thau')->nullable();
            $table->string('tu_ngay')->nullable();
            $table->string('den_ngay')->nullable();
            $table->string('ma_cskcb')->nullable();
            $table->string('loai_thuoc')->nullable();
            $table->string('loai_thau')->nullable();
            $table->string('ht_thau')->nullable();
            $table->timestamps();

            // Adding unique constraint
            $table->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'tt_thau']);

            //Adding index
            $table->index(['ma_thuoc', 'so_dang_ky', 'tt_thau']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medicine_catalogs');
    }
}
