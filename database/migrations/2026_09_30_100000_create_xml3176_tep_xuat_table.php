<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Yeu cau xuat tep chay nen cua man XML3176 (spec 2026-09-30).
 *
 * Nut "Xuat danh sach loi" tai truc tiep tra 504 tren prod: ngay 29/09/2026 (1.880 ho so,
 * 204.617 dong loi) mat 1.796 giay trong khi Cloudflare chi cho 100 giay. Nay moi lan bam la
 * mot dong o day; job nen tao tep, nguoi bam tai ve khi xong.
 */
class CreateXml3176TepXuatTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_tep_xuat', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('loai', 20);
            $table->text('bo_loc');
            $table->string('trang_thai', 20)->index();
            $table->string('duong_dan')->nullable();
            $table->unsignedBigInteger('kich_thuoc')->nullable();
            $table->text('loi')->nullable();
            $table->timestamp('bat_dau_luc')->nullable();
            $table->timestamp('xong_luc')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_tep_xuat');
    }
}
