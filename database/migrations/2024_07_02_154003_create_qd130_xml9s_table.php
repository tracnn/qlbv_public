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
            $table->string('ma_lk', 100)->unique();
            $table->string('ma_bhxh_nnd', 10)->nullable();
            $table->string('ma_the_nnd', 15)->nullable();
            $table->string('ho_ten_nnd', 255)->nullable();
            $table->string('ngaysinh_nnd', 8)->nullable();
            $table->string('ma_dantoc_nnd', 2)->nullable();
            $table->string('so_cccd_nnd', 15)->nullable();
            $table->string('ngaycap_cccd_nnd', 8)->nullable();
            $table->string('noicap_cccd_nnd', 1024)->nullable();
            $table->string('noi_cu_tru_nnd', 1024)->nullable();
            $table->string('ma_quoctich', 3)->nullable();
            $table->string('matinh_cu_tru', 3)->nullable();
            $table->string('mahuyen_cu_tru', 3)->nullable();
            $table->string('maxa_cu_tru', 5)->nullable();
            $table->string('ho_ten_cha', 255)->nullable();
            $table->string('ma_the_tam', 15)->nullable();
            $table->string('ho_ten_con', 255)->nullable();
            $table->unsignedTinyInteger('gioi_tinh_con')->nullable();
            $table->unsignedTinyInteger('so_con')->nullable();
            $table->unsignedTinyInteger('lan_sinh')->nullable();
            $table->unsignedTinyInteger('so_con_song')->nullable();
            $table->unsignedSmallInteger('can_nang_con')->nullable();
            $table->string('ngay_sinh_con', 12)->nullable();
            $table->string('noi_sinh_con', 1024)->nullable();
            $table->text('tinh_trang_con')->nullable();
            $table->unsignedTinyInteger('sinhcon_phauthuat')->nullable();
            $table->unsignedTinyInteger('sinhcon_duoi32tuan')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('nguoi_do_de', 255)->nullable();
            $table->string('nguoi_ghi_phieu', 255)->nullable();
            $table->string('ngay_ct', 8)->nullable();
            $table->string('so', 200)->nullable();
            $table->string('quyen_so', 200)->nullable();
            $table->string('ma_ttdv', 10)->nullable();
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
