<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml7sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml7s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('so_luu_tru')->nullable();
            $table->string('ma_yte')->nullable();
            $table->string('ma_khoa_rv')->nullable();
            $table->string('ngay_vao')->nullable();
            $table->string('ngay_ra')->nullable();
            $table->boolean('ma_dinh_chi_thai')->nullable();
            $table->string('nguyennhan_dinhchi')->nullable();
            $table->string('thoigian_dinhchi')->nullable();
            $table->unsignedTinyInteger('tuoi_thai')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->string('ghi_chu', 1500)->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->string('ma_bs')->nullable();
            $table->string('ten_bs')->nullable();
            $table->string('ngay_ct')->nullable();
            $table->string('ma_cha')->nullable();
            $table->string('ma_me')->nullable();
            $table->string('ma_the_tam')->nullable();
            $table->string('ho_ten_cha')->nullable();
            $table->string('ho_ten_me')->nullable();
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
        Schema::dropIfExists('xml3176_xml7s');
    }
}
