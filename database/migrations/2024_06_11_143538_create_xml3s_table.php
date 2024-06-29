<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_lk', 100);
            $table->integer('stt')->nullable();
            $table->string('ma_dich_vu', 255)->nullable();
            $table->string('ma_vat_tu', 255)->nullable();
            $table->integer('ma_nhom');
            $table->string('goi_vtyt', 2)->nullable();
            $table->string('ten_vat_tu', 1024)->nullable();
            $table->string('ten_dich_vu', 1024)->nullable();
            $table->string('don_vi_tinh', 50)->nullable();
            $table->integer('pham_vi');
            $table->double('so_luong');
            $table->double('don_gia');
            $table->string('tt_thau', 255)->nullable();
            $table->integer('tyle_tt');
            $table->double('thanh_tien');
            $table->double('t_trantt')->nullable();
            $table->integer('muc_huong');
            $table->double('t_nguonkhac')->nullable();
            $table->double('t_bntt')->nullable();
            $table->double('t_bhtt');
            $table->double('t_bncct')->nullable();
            $table->double('t_ngoaids')->nullable();
            $table->string('ma_khoa', 15);
            $table->string('ma_giuong', 14)->nullable();
            $table->string('ma_bac_si', 255);
            $table->string('ma_benh', 255);
            $table->string('ngay_yl', 12);
            $table->string('ngay_kq', 12)->nullable();
            $table->integer('ma_pttt');
            $table->timestamps();

            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_lk');
            $table->index('ngay_yl');
            $table->index('ngay_kq');
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
        Schema::dropIfExists('xml3s');
    }
}