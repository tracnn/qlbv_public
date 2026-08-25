<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * ANH CHUP nguyen van tung dong cua tep Excel.
 *
 * VI SAO MOT COT du_lieu CHU KHONG SAU BANG RIENG: sau mau co 11-37 cot khac nhau hoan
 * toan, va bang danh muc moi la noi du lieu co hinh thu de truy van. Bang nay chi co
 * MOT nhiem vu - dung lai dung XML da gui. Sau bang chi de phuc vu moi viec do la sau
 * bang phai sua moi lan BHXH doi mau.
 *
 * VI SAO longText CHU KHONG json(): du an chay Laravel 5.5 / PHP 7.0, chua migration nao
 * dung kieu json, va bo test chay tren SQLite. longText hoat dong giong nhau o moi noi.
 */
class CreateTt12DongTable extends Migration
{
    public function up()
    {
        Schema::create('tt12_dong', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();
            $table->integer('stt');

            $table->longText('du_lieu')->nullable();

            // Bon cot rut ra de loc/tim tren man hinh. Chung LAP LAI gia tri da co trong
            // du_lieu - co y: truy van LIKE tren mot cot longText JSON khong dung duoc
            // index, va man danh sach can loc theo ma va khoang ngay.
            $table->string('ma', 255)->nullable()->index();
            $table->text('ten')->nullable();
            $table->string('tu_ngay', 8)->nullable()->index();
            $table->string('den_ngay', 8)->nullable();

            $table->timestamps();

            // STT khong duoc trung trong mot ho so: XML gui len co the co hai dong cung
            // so thu tu, va cong tra 205 sau khi ta da ky xong.
            $table->unique(['ho_so_id', 'stt'], 'unique_tt12_dong');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tt12_dong');
    }
}
