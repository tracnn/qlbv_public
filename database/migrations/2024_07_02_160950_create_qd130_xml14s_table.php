<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml14sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml14s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('so_giayhen_kl', 50)->nullable();
            $table->string('ma_cskcb', 5)->nullable();
            $table->string('ho_ten', 255)->nullable();
            $table->string('ngay_sinh', 12)->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('dia_chi', 1024)->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->string('gt_the_den')->nullable();
            $table->string('ngay_vao', 12)->nullable();
            $table->string('ngay_vao_noi_tru', 12)->nullable();
            $table->string('ngay_ra', 12)->nullable();
            $table->string('ngay_hen_kl', 12)->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->string('ma_benh_chinh', 100)->nullable();
            $table->string('ma_benh_kt', 100)->nullable();
            $table->string('ma_benh_yhct', 255)->nullable();
            $table->string('ma_doituong_kcb', 4)->nullable();
            $table->string('ma_bac_si', 255)->nullable();
            $table->string('ma_ttdv', 10)->nullable();
            $table->string('ngay_ct', 8)->nullable();
            $table->text('du_phong')->nullable();
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
        Schema::dropIfExists('qd130_xml14s');
    }
}
