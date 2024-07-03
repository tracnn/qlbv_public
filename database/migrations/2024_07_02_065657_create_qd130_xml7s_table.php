<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml7sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml7s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk', 100)->unique();
            $table->string('so_luu_tru', 200)->nullable();
            $table->string('ma_yte', 200)->nullable();
            $table->string('ma_khoa_rv', 200)->nullable();
            $table->string('ngay_vao', 12)->nullable();
            $table->string('ngay_ra', 12)->nullable();
            $table->boolean('ma_dinh_chi_thai')->nullable();
            $table->string('nguyennhan_dinhchi')->nullable();
            $table->string('thoigian_dinhchi', 12)->nullable();
            $table->unsignedTinyInteger('tuoi_thai')->nullable();
            $table->string('chan_doan_rv', 1500)->nullable();
            $table->string('pp_dieutri', 1500)->nullable();
            $table->string('ghi_chu', 1500)->nullable();
            $table->string('ma_ttdv', 10)->nullable();
            $table->string('ma_bs', 200)->nullable();
            $table->string('ten_bs', 255)->nullable();
            $table->string('ngay_ct', 8)->nullable();
            $table->string('ma_cha', 10)->nullable();
            $table->string('ma_me', 10)->nullable();
            $table->string('ma_the_tam', 15)->nullable();
            $table->string('ho_ten_cha', 255)->nullable();
            $table->string('ho_ten_me', 255)->nullable();
            $table->unsignedTinyInteger('so_ngay_nghi')->nullable();
            $table->string('ngoaitru_tungay', 8)->nullable();
            $table->string('ngoaitru_denngay', 8)->nullable();
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
        Schema::dropIfExists('qd130_xml7s');
    }
}
