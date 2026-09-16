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
 *
 * CSDL test qlbv_test LECH SCHEMA so voi cac migration that (xem chu thich trong phpunit.xml
 * canh DB_DATABASE=qlbv_test) - khong duoc chay 'php artisan migrate' day du len no. De bang
 * nay co mat trong qlbv_test (mot so test doc thang SHOW COLUMNS / Schema::hasTable), migrate
 * SCOPED chi mot tep nay vao mot thu muc tam:
 *   mkdir tmp_migrate_pl1
 *   cp database/migrations/2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php tmp_migrate_pl1/
 *   DB_DATABASE=qlbv_test php artisan migrate --path=tmp_migrate_pl1 --force
 *   rm -rf tmp_migrate_pl1
 * (Migrator chi glob dung thu muc tam nay nen chi CREATE dung mot bang, khong dung ai khac.)
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
