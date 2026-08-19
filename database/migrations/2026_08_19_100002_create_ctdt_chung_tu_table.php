<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtdtChungTuTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_chung_tu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();

            $table->string('loai_ho_so', 30)->index();       // gia tri LOAIHOSO nguyen van
            $table->string('ma_chung_tu', 100)->nullable()->index();

            // COT RUT GON - trung lap co chu dich. Chin loai chung tu dat ten truong khac
            // nhau (NGAY_SINH nguoi benh, NGAYSINH_NND nguoi me, NGAY_SINH_CON cua con),
            // khong rut gon thi moi truy van danh sach thanh UNION 9 nhanh.
            $table->string('ma_the', 20)->nullable()->index();
            $table->string('ho_ten')->nullable()->index();
            $table->string('ngay_sinh', 14)->nullable();
            $table->string('ngay_vao', 14)->nullable();
            $table->string('ngay_ra', 14)->nullable();

            $table->longText('noi_dung_goc')->nullable();

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_chung_tu');
    }
}
