<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Danh muc benh Phu luc I Thong tu 01/2025/TT-BYT: benh duoc tu den KCB tai co so cap
 * chuyen sau (ma doi tuong KCB 1.17).
 *
 * Moi dong la MOT MAU MA thuoc mot STT cua Phu luc I; mot STT co nhieu dong 'bao_gom' va
 * co the co dong 'tru'. Danh muc QUOC GIA, nap theo kieu THAY TRON BO: cot is_active la bat
 * buoc vi CatalogImportService dung chinh cot nay.
 */
class CreateBenhPl1CapChuyenSauTable extends Migration
{
    public function up()
    {
        Schema::create('benh_pl1_cap_chuyen_sau', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('stt');
            $table->string('ten_benh', 1024)->nullable();
            $table->string('ma_icd', 20);
            $table->string('loai', 10);
            $table->unsignedTinyInteger('tuoi_duoi')->nullable();
            $table->text('dieu_kien')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['stt', 'ma_icd', 'loai']);
            $table->index('ma_icd');
        });
    }

    public function down()
    {
        Schema::dropIfExists('benh_pl1_cap_chuyen_sau');
    }
}
