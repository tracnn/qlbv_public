<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml11sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml11s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('so_ct')->nullable();
            $table->string('so_seri')->nullable();
            $table->string('so_kcb')->nullable();
            $table->string('don_vi', 1024)->nullable();
            $table->string('ma_bhxh')->nullable();
            $table->string('ma_the_bhyt')->nullable();
            $table->text('chan_doan_rv')->nullable();
            $table->text('pp_dieutri')->nullable();
            $table->unsignedTinyInteger('ma_dinh_chi_thai')->nullable();
            $table->text('nguyennhan_dinhchi')->nullable();
            $table->unsignedTinyInteger('tuoi_thai')->nullable();
            $table->unsignedTinyInteger('so_ngay_nghi')->nullable();
            $table->string('tu_ngay')->nullable();
            $table->string('den_ngay')->nullable();
            $table->string('ho_ten_cha')->nullable();
            $table->string('ho_ten_me')->nullable();
            $table->string('ma_ttdv')->nullable();
            $table->string('ma_bs')->nullable();
            $table->string('ngay_ct')->nullable();
            $table->string('ma_the_tam')->nullable();
            $table->string('mau_so')->nullable();
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
        Schema::dropIfExists('xml3176_xml11s');
    }
}
