<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQd130Xml12sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qd130_xml12s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nguoi_chu_tri', 255);
            $table->string('chuc_vu', 1);
            $table->string('ngay_hop', 8);
            $table->string('ho_ten', 255);
            $table->string('ngay_sinh', 8);
            $table->string('so_cccd', 15);
            $table->string('ngay_cap_cccd', 8);
            $table->string('noi_cap_cccd', 1024);
            $table->string('dia_chi', 1024);
            $table->string('matinh_cu_tru', 3);
            $table->string('mahuyen_cu_tru', 3);
            $table->string('maxa_cu_tru', 5);
            $table->string('ma_bhxh', 10);
            $table->string('ma_the_bhyt', 15);
            $table->string('nghe_nghiep', 100);
            $table->string('dien_thoai', 15);
            $table->string('ma_doi_tuong', 20);
            $table->unsignedTinyInteger('kham_giam_dinh');
            $table->string('so_bien_ban', 200);
            $table->unsignedTinyInteger('tyle_ttct_cu');
            $table->string('dang_huong_che_do', 10);
            $table->string('ngay_chung_tu', 8);
            $table->string('so_giay_gioi_thieu', 200);
            $table->string('ngay_de_nghi', 8);
            $table->string('ma_donvi', 200);
            $table->string('gioi_thieu_cua', 1024);
            $table->text('ket_qua_kham')->nullable();
            $table->string('so_van_ban_can_cu', 200);
            $table->unsignedTinyInteger('tyle_ttct_moi');
            $table->unsignedTinyInteger('tong_tyle_ttct');
            $table->unsignedTinyInteger('dang_khuyettat');
            $table->unsignedTinyInteger('muc_do_khuyettat');
            $table->text('de_nghi')->nullable();
            $table->text('duoc_xacdinh')->nullable();
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
        Schema::dropIfExists('qd130_xml12s');
    }
}
