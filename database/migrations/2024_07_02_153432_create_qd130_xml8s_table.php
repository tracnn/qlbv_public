<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml8sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml8s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('ma_loai_kcb', 2);
            $table->string('ho_ten_cha', 255)->nullable();
            $table->string('ho_ten_me', 255)->nullable();
            $table->string('nguoi_giam_ho', 255)->nullable();
            $table->string('don_vi', 1024);
            $table->string('ngay_vao', 12);
            $table->string('ngay_ra', 12);
            $table->text('chan_doan_vao')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('qt_benhly')->nullable();
            $table->text('tomtat_kq')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ngay_sinhcon', 8)->nullable();
            $table->string('ngay_conchet', 8)->nullable();
            $table->unsignedTinyInteger('so_conchet')->nullable();
            $table->unsignedTinyInteger('ket_qua_dtri')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('ma_ttdv', 10);
            $table->string('ngay_ct', 8);
            $table->string('ma_the_tam', 15)->nullable();
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
        Schema::dropIfExists('qd130_xml8s');
    }
}
