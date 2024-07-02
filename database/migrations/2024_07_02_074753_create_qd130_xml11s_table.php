<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml11sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml11s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('so_ct', 200);
            $table->string('so_seri', 200);
            $table->string('so_kcb', 200);
            $table->string('don_vi', 1024);
            $table->string('ma_bhxh');
            $table->string('ma_the_bhyt')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->unsignedTinyInteger('ma_dinh_chi_thai')->nullable();
            $table->text('nguyennhan_dinhchi')->nullable();
            $table->unsignedTinyInteger('tuoi_thai')->nullable();
            $table->unsignedTinyInteger('so_ngay_nghi')->nullable();
            $table->string('tu_ngay', 8);
            $table->string('den_ngay', 8);
            $table->string('ho_ten_cha', 255)->nullable();
            $table->string('ho_ten_me', 255)->nullable();
            $table->string('ma_ttdv');
            $table->string('ma_bs', 200);
            $table->string('ngay_ct', 8);
            $table->string('ma_the_tam', 15)->nullable();
            $table->string('mau_so', 5)->default('CT07');
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
        Schema::dropIfExists('qd130_xml11s');
    }
}
