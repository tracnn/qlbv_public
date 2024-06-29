<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml2sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml2s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_lk', 100);
            $table->integer('stt')->nullable();
            $table->string('ma_thuoc', 255);
            $table->integer('ma_nhom');
            $table->string('ten_thuoc', 1024);
            $table->string('don_vi_tinh', 50);
            $table->string('ham_luong', 1024)->nullable();
            $table->string('duong_dung', 4)->nullable();
            $table->string('lieu_dung', 255)->nullable();
            $table->string('so_dang_ky', 100)->nullable();
            $table->string('tt_thau', 255)->nullable();
            $table->integer('pham_vi');
            $table->double('so_luong');
            $table->double('don_gia');
            $table->double('tyle_tt');
            $table->double('thanh_tien');
            $table->double('muc_huong');
            $table->double('t_nguon_khac')->nullable();
            $table->double('t_bntt')->nullable();
            $table->double('t_bhtt');
            $table->double('t_bncct')->nullable();
            $table->double('t_ngoaids')->nullable();
            $table->string('ma_khoa', 15);
            $table->string('ma_bac_si', 255);
            $table->string('ma_benh', 255);
            $table->string('ngay_yl', 12);
            $table->integer('ma_pttt');
            $table->timestamps();

            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ngay_yl');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xml2s');
    }
}