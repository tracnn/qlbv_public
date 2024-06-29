<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml1sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml1s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_lk', 100);
            $table->integer('stt')->nullable();
            $table->string('ma_bn', 100);
            $table->string('ho_ten', 100);
            $table->string('ngay_sinh', 100);
            $table->integer('gioi_tinh');
            $table->string('dia_chi', 1024);
            $table->string('ma_the', 255);
            $table->string('ma_dkbd', 255);
            $table->string('gt_the_tu', 255);
            $table->string('gt_the_den', 255)->nullable();
            $table->string('mien_cung_ct', 100)->nullable();
            $table->string('ten_benh', 2000);
            $table->string('ma_benh', 255);
            $table->string('ma_benhkhac', 255)->nullable();
            $table->integer('ma_lydo_vvien');
            $table->string('ma_noi_chuyen', 5)->nullable();
            $table->integer('ma_tai_nan')->nullable();
            $table->string('ngay_vao', 12);
            $table->string('ngay_ra', 12);
            $table->integer('so_ngay_dtri');
            $table->integer('ket_qua_dtri');
            $table->integer('tinh_trang_rv');
            $table->string('ngay_ttoan', 12)->nullable();
            $table->double('t_thuoc')->nullable();
            $table->double('t_vtyt')->nullable();
            $table->double('t_tongchi');
            $table->double('t_bntt')->nullable();
            $table->double('t_bncct')->nullable();
            $table->double('t_bhtt');
            $table->double('t_nguonkhac')->nullable();
            $table->double('t_ngoaids')->nullable();
            $table->integer('nam_qt');
            $table->integer('thang_qt');
            $table->integer('ma_loai_kcb');
            $table->string('ma_khoa', 15);
            $table->string('ma_cskcb', 5);
            $table->string('ma_khuvuc', 2)->nullable();
            $table->string('ma_pttt_qt', 255)->nullable();
            $table->double('can_nang')->nullable();
            $table->timestamps();

            $table->unique('ma_lk');
            $table->unique(['ma_lk', 'stt'], 'unique_ma_lk_stt');
            $table->index('ma_khoa');
            $table->index('ma_loai_kcb');
            $table->index('ngay_ttoan');
            $table->index('ngay_ra');
            $table->index('ngay_vao');
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
        Schema::dropIfExists('xml1s');
    }
}