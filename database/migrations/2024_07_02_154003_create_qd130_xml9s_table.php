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
            $table->string('ma_bhxh_nnd', 10);
            $table->string('ma_the_nnd', 15);
            $table->string('ho_ten_nnd', 255);
            $table->string('ngaysinh_nnd', 8);
            $table->string('ma_dantoc_nnd', 2);
            $table->string('so_cccd_nnd', 15);
            $table->string('ngaycap_cccd_nnd', 8);
            $table->string('noicap_cccd_nnd', 1024);
            $table->string('noi_cu_tru_nnd', 1024);
            $table->string('ma_quoctich', 3);
            $table->string('matinh_cu_tru', 3);
            $table->string('mahuyen_cu_tru', 3);
            $table->string('maxa_cu_tru', 5);
            $table->string('ho_ten_cha', 255);
            $table->string('ma_the_tam', 15);
            $table->string('ho_ten_con', 255);
            $table->unsignedTinyInteger('gioi_tinh_con');
            $table->unsignedTinyInteger('so_con');
            $table->unsignedTinyInteger('lan_sinh');
            $table->unsignedTinyInteger('so_con_song');
            $table->unsignedSmallInteger('can_nang_con');
            $table->string('ngay_sinh_con', 12);
            $table->string('noi_sinh_con', 1024);
            $table->text('tinh_trang_con')->nullable();
            $table->unsignedTinyInteger('sinhcon_phauthuat');
            $table->unsignedTinyInteger('sinhcon_duoi32tuan');
            $table->text('ghi_chu')->nullable();
            $table->string('nguoi_do_de', 255);
            $table->string('nguoi_ghi_phieu', 255);
            $table->string('ngay_ct', 8);
            $table->string('so', 200);
            $table->string('quyen_so', 200);
            $table->string('ma_ttdv', 10);
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
