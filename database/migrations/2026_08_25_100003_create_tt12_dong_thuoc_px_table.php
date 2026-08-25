<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bang con DS_THUOCPX > TT_THUOCPX cua MAU_05 (thuoc phong xa).
 *
 * Khong nhet vao JSON cua dong cha: cac the con phai dung ra XML theo dung THU TU va
 * mot dong cha co the co nhieu dong con.
 */
class CreateTt12DongThuocPxTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_dong_thuoc_px', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dong_id')->index();
            $table->integer('stt')->nullable();

            $table->string('ma_thuoc', 50)->nullable();
            $table->text('ten_thuoc')->nullable();
            $table->string('so_dang_ky', 100)->nullable();
            $table->text('don_vi_tinh')->nullable();
            $table->text('tt_thau')->nullable();
            $table->string('don_gia_thuoc', 20)->nullable();
            $table->string('dm_nsx_cdd', 20)->nullable();
            $table->string('dm_thucte_cdd', 20)->nullable();
            $table->string('lieu_bq_px', 20)->nullable();
            $table->string('tl_thucte_bq_px', 20)->nullable();
            $table->string('thanh_tien_thuoc', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_dong_thuoc_px');
    }
}
