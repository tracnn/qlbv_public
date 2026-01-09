<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXml3176Xml12sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml3176_xml12s', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nguoi_chu_tri');
            $table->string('chuc_vu');
            $table->string('ngay_hop');
            $table->string('ho_ten');
            $table->string('ngay_sinh');
            $table->string('so_cccd');
            $table->string('ngay_cap_cccd');
            $table->string('noi_cap_cccd', 1024);
            $table->string('dia_chi', 1024);
            $table->string('matinh_cu_tru');
            $table->string('mahuyen_cu_tru');
            $table->string('maxa_cu_tru');
            $table->string('ma_bhxh');
            $table->string('ma_the_bhyt');
            $table->string('nghe_nghiep');
            $table->string('dien_thoai');
            $table->string('ma_doi_tuong');
            $table->unsignedTinyInteger('kham_giam_dinh');
            $table->string('so_bien_ban');
            $table->unsignedTinyInteger('tyle_ttct_cu');
            $table->string('dang_huong_che_do');
            $table->string('ngay_chung_tu');
            $table->string('so_giay_gioi_thieu');
            $table->string('ngay_de_nghi');
            $table->string('ma_donvi');
            $table->string('gioi_thieu_cua', 1024);
            $table->text('ket_qua_kham')->nullable();
            $table->string('so_van_ban_can_cu');
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
        Schema::dropIfExists('xml3176_xml12s');
    }
}
