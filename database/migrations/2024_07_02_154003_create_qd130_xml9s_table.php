<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml9sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml9s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_lk')->unique();
            $table->string('ma_bhxh_nnd')->nullable();
            $table->string('ma_the_nnd')->nullable();
            $table->string('ho_ten_nnd')->nullable();
            $table->string('ngaysinh_nnd')->nullable();
            $table->string('ma_dantoc_nnd')->nullable();
            $table->string('so_cccd_nnd')->nullable();
            $table->string('ngaycap_cccd_nnd')->nullable();
            $table->string('noicap_cccd_nnd', 1024)->nullable();
            $table->string('noi_cu_tru_nnd', 1024)->nullable();
            $table->string('ma_quoctich')->nullable();
            $table->string('matinh_cu_tru')->nullable();
            $table->string('mahuyen_cu_tru')->nullable();
            $table->string('maxa_cu_tru')->nullable();
            $table->string('ho_ten_cha')->nullable();
            $table->string('ma_the_tam')->nullable();
            $table->string('ho_ten_con')->nullable();
            $table->unsignedTinyInteger('gioi_tinh_con')->nullable();
            $table->unsignedTinyInteger('so_con')->nullable();
            $table->unsignedTinyInteger('lan_sinh')->nullable();
            $table->unsignedTinyInteger('so_con_song')->nullable();
            $table->unsignedSmallInteger('can_nang_con')->nullable();
            $table->string('ngay_sinh_con')->nullable();
            $table->string('noi_sinh_con', 1024)->nullable();
            $table->text('tinh_trang_con')->nullable();
            $table->unsignedTinyInteger('sinhcon_phauthuat')->nullable();
            $table->unsignedTinyInteger('sinhcon_duoi32tuan')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('nguoi_do_de')->nullable();
            $table->string('nguoi_ghi_phieu')->nullable();
            $table->string('ngay_ct')->nullable();
            $table->string('so')->nullable();
            $table->string('quyen_so')->nullable();
            $table->string('ma_ttdv')->nullable();
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
        Schema::dropIfExists('qd130_xml9s');
    }
}
