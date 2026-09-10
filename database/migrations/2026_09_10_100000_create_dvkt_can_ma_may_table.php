<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Danh muc DVKT bat buoc phai gui kem ma may thuc hien, do BHXH ban hanh.
 *
 * Danh muc QUOC GIA (khong theo co so) va nap theo kieu THAY TRON BO: cot is_active la
 * bat buoc vi co che lam moi tron bo cua CatalogImportService dung chinh cot nay.
 */
class CreateDvktCanMaMayTable extends Migration
{
    public function up()
    {
        Schema::create('dvkt_can_ma_may', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_dvkt', 50)->unique();
            $table->string('ten_dvkt', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dvkt_can_ma_may');
    }
}
